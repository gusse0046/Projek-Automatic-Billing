import os
from flask import Flask, request, jsonify
from flask_cors import CORS
from pyrfc import Connection
from pyrfc import LogonError, CommunicationError, ABAPRuntimeError, ABAPApplicationError

app = Flask(__name__)
CORS(app)

def connect_sap(username=None, password=None):
    username = username or os.environ.get('SAP_USERNAME')
    password = password or os.environ.get('SAP_PASSWORD')
    if not username or not password:
        raise Exception("SAP credentials not provided.")
    
    return Connection(
        user=username,
        passwd=password,
        ashost='192.168.254.154',
        sysnr='01',
        client='300',
        lang='EN',
      
    )

@app.route('/api/sap-login', methods=['POST'])
def sap_login():
    data = request.json
    try:
        conn = connect_sap(data['username'], data['password'])
        # test with simple RFC call
        conn.ping()
        conn.close()
        return jsonify({'status': 'connected'})
    except (LogonError, CommunicationError, ABAPRuntimeError, ABAPApplicationError) as e:
        return jsonify({'error': str(e)}), 401
    except Exception as e:
        return jsonify({'error': 'Connection failed: ' + str(e)}), 500

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({'status': 'Python RFC Service is running'})

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=51)