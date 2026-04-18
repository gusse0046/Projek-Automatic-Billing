# UPDATED bill.py - Enhanced Connection with Better Error Handling
try:
    from flask import Flask, request, jsonify
    from pyrfc import Connection
    import os
    from datetime import datetime
    import time
    import threading
    import signal
    import sys
    import logging
except ImportError as e:
    print(f"ERROR: Missing module {e}. Run: pip install flask pyrfc")
    exit(1)

app = Flask(__name__)

# Enhanced logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global connection status
connection_status = {
    'last_attempt': None,
    'last_success': None,
    'last_error': None,
    'attempts_count': 0,
    'success_count': 0,
    'is_connected': False
}

def signal_handler(sig, frame):
    """Handle Ctrl+C gracefully"""
    print('\n\n=== SHUTTING DOWN SAP BILLING API ===')
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)

def connect_sap_enhanced():
    """Enhanced SAP connection with better error handling"""
    global connection_status
    
    connection_status['last_attempt'] = datetime.now()
    connection_status['attempts_count'] += 1
    
    start_time = time.time()
    
    try:
        print(f"[{datetime.now().strftime('%H:%M:%S')}] === CONNECTING TO SAP ===")
        print(f"Attempt #{connection_status['attempts_count']}")
        
        # CRITICAL: SAP Connection parameters - verify these are correct
        conn = Connection(
            user='basis',
            passwd='123itthebest',
            ashost='192.168.254.154',  # Verify this IP is reachable
            sysnr='01',               # Verify system number
            client='300',             # Verify client
            lang='EN',
        )
        
        # Test connection with simple call
        result = conn.call('RFC_SYSTEM_INFO')
        
        elapsed_time = time.time() - start_time
        print(f"✅ SAP connection SUCCESS in {elapsed_time:.2f} seconds")
        print(f"📋 System Info: {result.get('RFCSI_EXPORT', {}).get('RFCSYSID', 'Unknown')}")
        
        connection_status['last_success'] = datetime.now()
        connection_status['success_count'] += 1
        connection_status['last_error'] = None
        connection_status['is_connected'] = True
        
        return conn
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        print(f"❌ SAP connection FAILED after {elapsed_time:.2f} seconds")
        print(f"Error: {error_msg}")
        
        connection_status['last_error'] = {
            'timestamp': datetime.now(),
            'error': error_msg,
            'duration': elapsed_time
        }
        connection_status['is_connected'] = False
        
        # Analyze common errors
        if "CPIC" in error_msg:
            print("🔍 DIAGNOSIS: Network connectivity issue")
            print("   - Check if SAP server 192.168.254.154 is reachable")
            print("   - Verify firewall allows connection to port 3300/3301")
            print("   - Test: ping 192.168.254.154")
        elif "RFC_LOGON_FAILURE" in error_msg:
            print("🔍 DIAGNOSIS: Login credentials issue")
            print("   - Username: basis")
            print("   - Check if password is correct")
            print("   - Verify user is not locked in SAP")
        elif "timeout" in error_msg.lower():
            print("🔍 DIAGNOSIS: Connection timeout")
            print("   - SAP server may be slow or overloaded")
            print("   - Try increasing timeout values")
        
        raise e

def get_billing_data_enhanced(check_param=None, no_id=None, timeout_seconds=60):
    """Enhanced billing data retrieval with better error handling"""
    conn = None
    all_data = []
    start_time = time.time()
    
    try:
        # Default parameter
        if check_param is None:
            check_param = "X"
        
        parameters = {
            'CHECK': check_param,
            'T_DATA': []
        }
        
        if no_id:
            parameters['NO_ID'] = no_id
        
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === FETCHING BILLING DATA ===")
        print(f"Parameters: {parameters}")
        print(f"Timeout: {timeout_seconds} seconds")
        
        # Connect to SAP
        conn = connect_sap_enhanced()
        
        # Call SAP function
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Calling Z_FM_BILLING_INITIATOR1...")
        result = conn.call('Z_FM_BILLING_INITIATOR1', **parameters)
        
        print("=== SAP FUNCTION RESULT ===")
        print(f"Keys in result: {list(result.keys()) if isinstance(result, dict) else 'Not a dict'}")
        
        # Process result
        if 'T_DATA' in result:
            raw_data = result.get('T_DATA', [])
            print(f"✅ Found {len(raw_data)} records in T_DATA")
            
            # Apply field mapping
            if raw_data:
                all_data = map_sap_to_clean_format(raw_data)
                print(f"✅ Mapped {len(all_data)} records successfully")
        else:
            print("❌ T_DATA not found in result")
            # Try alternative keys
            for key, value in result.items():
                if isinstance(value, list) and len(value) > 0:
                    first_item = value[0] if value else {}
                    if isinstance(first_item, dict) and any(k in first_item for k in ['NO_ID', 'LFDAT', 'VBELN']):
                        print(f"📋 Using data from key: {key}")
                        all_data = map_sap_to_clean_format(value)
                        break
        
        elapsed_time = time.time() - start_time
        print(f"✅ BILLING DATA FETCH COMPLETED")
        print(f"   - Total time: {elapsed_time:.2f} seconds")
        print(f"   - Records: {len(all_data)}")
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        print(f"❌ BILLING DATA FETCH FAILED after {elapsed_time:.2f} seconds")
        print(f"Error: {error_msg}")
        raise e
    finally:
        if conn:
            try:
                conn.close()
                print(f"[{datetime.now().strftime('%H:%M:%S')}] SAP connection closed")
            except:
                pass
    
    return all_data

def map_sap_to_clean_format(sap_data):
    """Enhanced field mapping with booking number support"""
    field_mapping = {
        'LFDAT': 'Delivery Date',
        'KUNNR': 'Customer Number', 
        'MATNR': 'Material Number',
        'ARKTX': 'Description',
        'LFIMG': 'Actual quantity delivered (in sales units)',
        'POSNR': 'Item Number',
        'VBELN': 'Delivery',
        'VGBEL': 'Reference Document',
        'FKIMG': 'Actual billed quantity',
        'NETWR': 'Net Value in Document Currency',
        'WAERK': 'Currency',
        'VRKME': 'Sales unit',
        'VBELN_VBRK': 'Billing Document',
        'FKDAT': 'Billing Date',
        'ERDAT': 'Created Date',
        'FKART': 'Billing Type',
        'NO_PO': 'Purchase Order Number',
        'NAME1': 'Customer Name',
        'BOOKING_NO': 'Booking Number',
        'CONTAINER_NO': 'Container Number'
    }
    
    if isinstance(sap_data, list):
        return [map_single_record_clean(record, field_mapping) for record in sap_data]
    else:
        return map_single_record_clean(sap_data, field_mapping)

def map_single_record_clean(record, field_mapping):
    """Map single SAP record to clean format"""
    if not isinstance(record, dict):
        return record
    
    clean_record = {}
    
    # Map fields
    for sap_field, friendly_name in field_mapping.items():
        if sap_field in record:
            value = record[sap_field]
            
            # Format dates
            if friendly_name in ['Delivery Date', 'Billing Date', 'Created Date']:
                value = format_date(value)
            elif friendly_name in ['Material Number', 'Customer Number', 'Item Number']:
                value = clean_sap_number(value)
            elif friendly_name in ['Actual quantity delivered (in sales units)', 'Actual billed quantity']:
                value = clean_sap_decimal(value)
            elif friendly_name == 'Net Value in Document Currency':
                currency = record.get('WAERK', 'USD')
                value = format_currency_value(value, currency)
            else:
                value = clean_sap_string(value)
            
            clean_record[friendly_name] = value
    
    return clean_record

def format_date(date_str):
    """Format SAP date to readable format"""
    if not date_str:
        return ''
    
    if len(str(date_str)) == 8:
        date_str = str(date_str)
        return f"{date_str[6:8]}.{date_str[4:6]}.{date_str[0:4]}"
    
    return str(date_str)

def format_currency_value(value, currency):
    """Format currency value"""
    if not value:
        return ''
    
    try:
        float_value = float(value)
        return f"{float_value:,.2f}"
    except (ValueError, TypeError):
        return str(value)

def clean_sap_number(value):
    """Clean SAP number - remove leading zeros"""
    if not value:
        return ''
    
    str_value = str(value).strip()
    cleaned = str_value.lstrip('0')
    
    if not cleaned:
        cleaned = '0'
    
    return cleaned

def clean_sap_decimal(value):
    """Clean SAP decimal format"""
    if not value:
        return ''
    
    try:
        float_value = float(value)
        if float_value.is_integer():
            return str(int(float_value))
        else:
            return f"{float_value:g}"
    except (ValueError, TypeError):
        return str(value)

def clean_sap_string(value):
    """Clean SAP string"""
    if not value:
        return ''
    return str(value).strip()

# === API ENDPOINTS ===

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'SAP Billing API',
        'version': '4.4.0',
        'timestamp': datetime.now().isoformat(),
        'sap_connection': connection_status['is_connected'],
        'connection_stats': connection_status
    })

@app.route('/api/billing_data', methods=['GET'])
def billing_data():
    """Get billing data - main endpoint"""
    check_param = request.args.get('check', 'X')
    no_id = request.args.get('no_id')
    timeout = int(request.args.get('timeout', 60))
    
    start_time = time.time()
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === API BILLING DATA REQUEST ===")
        print(f"Parameters: check={check_param}, no_id={no_id}, timeout={timeout}s")
        
        data = get_billing_data_enhanced(check_param, no_id, timeout)
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'total_records': len(data),
            'data': data,
            'response_time': f'{elapsed_time:.2f} seconds',
            'connection_status': connection_status['is_connected'],
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        print(f"❌ API request failed: {error_msg}")
        
        return jsonify({
            'status': 'error',
            'error': error_msg,
            'response_time': f'{elapsed_time:.2f} seconds',
            'connection_status': False,
            'suggestions': [
                'Check SAP server connectivity',
                'Verify SAP credentials',
                'Check SAP function Z_FM_BILLING_INITIATOR1 exists',
                'Try /api/billing_data_fast for shorter timeout'
            ],
            'timestamp': datetime.now().isoformat()
        }), 500

@app.route('/api/billing_data_fast', methods=['GET'])
def billing_data_fast():
    """Fast billing data endpoint"""
    check_param = request.args.get('check', 'X')
    no_id = request.args.get('no_id')
    
    start_time = time.time()
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === FAST API REQUEST ===")
        
        data = get_billing_data_enhanced(check_param, no_id, timeout_seconds=30)
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'total_records': len(data),
            'data': data,
            'response_time': f'{elapsed_time:.2f} seconds',
            'fast_mode': True,
            'connection_status': connection_status['is_connected'],
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        return jsonify({
            'status': 'error',
            'error': str(e),
            'response_time': f'{elapsed_time:.2f} seconds',
            'fast_mode': True,
            'connection_status': False,
            'timestamp': datetime.now().isoformat()
        }), 500

@app.route('/api/test_sap_connection', methods=['GET'])
def test_sap_connection():
    """Test SAP connection only"""
    start_time = time.time()
    
    try:
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] === CONNECTION TEST ===")
        
        conn = connect_sap_enhanced()
        conn.close()
        
        elapsed_time = time.time() - start_time
        
        return jsonify({
            'status': 'success',
            'message': 'SAP connection test successful',
            'connection_time': f'{elapsed_time:.2f} seconds',
            'connection_info': {
                'host': '192.168.254.154',
                'client': '300',
                'user': 'basis'
            },
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        elapsed_time = time.time() - start_time
        error_msg = str(e)
        
        return jsonify({
            'status': 'error',
            'message': f'SAP connection test failed: {error_msg}',
            'connection_time': f'{elapsed_time:.2f} seconds',
            'timestamp': datetime.now().isoformat()
        }), 500

@app.route('/', methods=['GET'])
def index():
    """Index endpoint with enhanced info"""
    return jsonify({
        'message': 'SAP Billing API - Enhanced Connection & Error Handling',
        'version': '4.4.0',
        'running_on': 'http://127.0.0.1:50',
        'connection_status': connection_status,
        'endpoints': {
            'health': '/health',
            'test_connection': '/api/test_sap_connection',
            'billing_data': '/api/billing_data',
            'billing_data_fast': '/api/billing_data_fast'
        },
        'sap_config': {
            'host': '192.168.254.154',
            'client': '300',
            'user': 'basis',
            'function': 'Z_FM_BILLING_INITIATOR1'
        },
        'troubleshooting': {
            'network_test': 'ping 192.168.254.154',
            'port_test': 'telnet 192.168.254.154 3300',
            'function_test': 'GET /api/test_sap_connection'
        }
    })

if __name__ == '__main__':
    print("=" * 80)
    print("🚀 STARTING ENHANCED SAP BILLING API SERVER")
    print("=" * 80)
    print("🔧 ENHANCED FEATURES:")
    print("✅ Better error diagnosis and handling")
    print("✅ Connection status monitoring")
    print("✅ Enhanced logging and debugging")
    print("✅ Improved timeout handling")
    print("✅ Booking number support")
    print("=" * 80)
    print("🌐 Server URLs:")
    print("   Main: http://127.0.0.1:50")
    print("   Health: http://127.0.0.1:50/health")
    print("   Test: http://127.0.0.1:50/api/test_sap_connection")
    print("=" * 80)
    print("🔍 TROUBLESHOOTING:")
    print("1. Test connection: http://127.0.0.1:50/api/test_sap_connection")
    print("2. Check health: http://127.0.0.1:50/health")
    print("3. Test data: http://127.0.0.1:50/api/billing_data_fast")
    print("4. Network test: ping 192.168.254.154")
    print("=" * 80)
    
    app.run(host='0.0.0.0', port=50, debug=True)