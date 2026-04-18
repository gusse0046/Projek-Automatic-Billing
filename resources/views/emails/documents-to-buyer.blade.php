<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $enhanced_subject ?? 'KMI Finance Document' }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f4f4;
        }
        .container { 
            max-width: 700px; 
            margin: 0 auto; 
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content { 
            padding: 30px 20px; 
            background: #f8f9fa; 
        }
        .info-box { 
            background: white; 
            padding: 20px; 
            margin: 15px 0; 
            border-left: 4px solid #007bff; 
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .buyer-greeting { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            text-align: center;
        }
        .buyer-greeting h2 {
            margin: 0 0 10px 0;
            font-size: 22px;
        }
        .billing-info { 
            background: #e8f5e8; 
            border-left: 4px solid #4caf50; 
        }
        .export-info { 
            background: #e3f2fd; 
            border-left: 4px solid #2196f3; 
        }
        .footer { 
            background: #343a40; 
            color: white; 
            padding: 25px 20px; 
            text-align: center; 
        }
        .footer-contact {
            background: #495057;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .highlight { 
            background: #fff3cd; 
            padding: 15px; 
            border-radius: 6px; 
            border-left: 4px solid #ffc107; 
            font-style: italic;
            margin: 10px 0;
        }
        .billing-highlight {
            background: #d4edda;
            border-left: 4px solid #155724;
            color: #155724;
        }
        .export-highlight {
            background: #cce7ff;
            border-left: 4px solid #004085;
            color: #004085;
        }
        .booking-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    @php
        // ✅ FIXED: Always use billing_document as display number
        $display_number = $billing_document ?? $delivery_order;
        $has_billing_document = isset($billing_document) && !empty($billing_document) && $billing_document !== $delivery_order;
        $is_billing_email = $has_billing_document;
        
        // ✅ Enhanced subject - prioritize billing document
        if (!isset($enhanced_subject)) {
            $enhanced_subject = "Doc {$display_number}";
            if (isset($booking_number) && !empty($booking_number)) {
                $enhanced_subject .= " - Booking: {$booking_number}";
            }
        }
    @endphp

    <div class="container">
        <div class="header">
            <h1>KMI Finance - Official Billing Document</h1>
            <p>{{ $enhanced_subject ?? "Doc {$display_number}" }}</p>
        </div>
        
        <div class="content">
            {{-- Buyer greeting --}}
            <div class="buyer-greeting">
                <h2>
                    @if(isset($primary_contact_name) && $primary_contact_name)
                        Dear {{ $primary_contact_name }},
                    @elseif(isset($buyer_email_contact) && $buyer_email_contact)
                        Dear {{ $buyer_email_contact }},
                    @else
                        Dear Buyer,
                    @endif
                </h2>
                <p>
                    @if($is_billing_email)
                        Please find the attached billing documents for your review and payment processing.
                    @else
                        Please find the attached export documents for your review and processing.
                    @endif
                </p>
            </div>

            {{-- Document Information - ✅ FIXED: Proper @if @else @endif structure --}}
            <div class="info-box {{ $is_billing_email ? 'billing-info' : 'export-info' }}">
                <h3>
                    @if($is_billing_email)
                        Billing Document Information
                    @else
                        Export Document Information
                    @endif
                </h3>
                
                @if($is_billing_email && $billing_document)
                    {{-- BILLING EMAIL - Show billing number prominently --}}
                    <p>
                        <strong>Billing Number:</strong> 
                        <span style="font-size: 18px; color: #2e7d32;">{{ $billing_document }}</span>
                    </p>
                    <p><strong>Status:</strong> Billing Document Available</p>
                    
                    @if(isset($booking_number) && $booking_number)
                        <p>
                            <strong>Booking Number:</strong> 
                            <span style="font-size: 18px; color: #2e7d32;">{{ $booking_number }}</span>
                        </p>
                    @endif
                @else
                    {{-- EXPORT EMAIL - Show delivery order --}}
                    <p>
                        <strong>Delivery Order:</strong> 
                        <span style="font-size: 18px; color: #1976d2;">{{ $delivery_order }}</span>
                    </p>
                    <p><strong>Status:</strong> Billing Document Pending</p>
                    
                    @if(isset($booking_number) && $booking_number)
                        <div class="booking-info">
                            <strong>📋 Booking Number:</strong> {{ $booking_number }}
                        </div>
                    @endif
                @endif
                
                <p><strong>Customer:</strong> {{ $customer_name ?? 'Not specified' }}</p>
                <p><strong>Date Processed:</strong> {{ isset($sent_at) ? date('d M Y, H:i', strtotime($sent_at)) : date('d M Y, H:i') }} WIB</p>
            </div>
            
            {{-- Message --}}
            @if(isset($email_message) && $email_message)
                <div class="info-box">
                    <h3>Message from KMI Finance Team</h3>
                    <div class="highlight {{ $is_billing_email ? 'billing-highlight' : 'export-highlight' }}">
                        {!! nl2br(e($email_message)) !!}
                    </div>
                </div>
            @else
                <div class="info-box">
                    <h3>Message</h3>
                    <div class="highlight {{ $is_billing_email ? 'billing-highlight' : 'export-highlight' }}">
                        @php
                            $contact_name = $primary_contact_name ?? $buyer_contact_name ?? null;
                            $greeting = $contact_name ? "Dear {$contact_name}," : "Dear Buyer,";
                        @endphp
                        
                        {{ $greeting }}<br><br>
                        
                        @if($is_billing_email)
                            Please find the attached billing documents for Invoice {{ $billing_document }}.
                            @if(isset($booking_number) && $booking_number)
                                <br>Booking Reference: {{ $booking_number }}
                            @endif
                            <br><br>
                            Please review all documents carefully for payment processing.<br><br>
                        @else
                            Please find the attached export documents for Delivery {{ $delivery_order }}.
                            @if(isset($booking_number) && $booking_number)
                                <br>Booking Reference: {{ $booking_number }}
                            @endif
                            <br><br>
                            Please review all documents carefully. Billing document will be sent separately once available.<br><br>
                        @endif
                        
                        Best Regards,<br>
                        KMI Finance - Account Receivable
                    </div>
                </div>
            @endif
        </div>
        
        <div class="footer">
            <h3 style="margin-top: 0; color: #fff;">Best Regards</h3>
            <p style="font-size: 18px; margin: 10px 0;"><strong>KMI Finance - Account Receivable</strong></p>
           
            <div class="footer-contact">
                <h4 style="margin-top: 0;">Contact Information</h4>
                <p>Email: ar.kmi@pawindo.com - finc.smg@pawindo.com</p>
                <p>Department: KMI Finance - Account Receivable</p>
                <p>
                    @if($is_billing_email)
                        System: Enhanced CC Billing System
                    @else
                        System: Enhanced CC Export Documentation System
                    @endif
                </p>
            </div>
            
            {{-- Enhanced footer message with CC info --}}
            <div style="background: #495057; padding: 15px; margin: 15px 0; border-radius: 4px;">
                @if($is_billing_email)
                    <p style="margin: 0; font-size: 14px;">
                        <strong>Email System:</strong> This billing email for Invoice {{ $billing_document }} was sent to 
                        {{ $total_recipients ?? 1 }} recipients (1 primary + {{ isset($cc_emails) ? count($cc_emails) : 0 }} CC) 
                        to ensure all stakeholders receive the documents simultaneously.
                        @if(isset($booking_number) && $booking_number)
                            <br><strong>Booking Reference:</strong> {{ $booking_number }}
                        @endif
                    </p>
                @else
                    <p style="margin: 0; font-size: 14px;">
                        <strong>📧 CC Email System:</strong> This export documentation for Delivery {{ $delivery_order }} was sent to 
                        {{ $total_recipients ?? 1 }} recipients (1 primary + {{ isset($cc_emails) ? count($cc_emails) : 0 }} CC).
                        Billing document will be sent separately once available.
                        @if(isset($booking_number) && $booking_number)
                            <br><strong>Booking Reference:</strong> {{ $booking_number }}
                        @endif
                    </p>
                @endif
            </div>
            
            {{-- System information --}}
            <div style="border-top: 1px solid #495057; padding-top: 15px; margin-top: 15px;">
                <p style="font-size: 11px; color: #6c757d; margin: 10px 0 0 0;">
                    This is an automated message from the KMI Finance CC Email System.
                </p>
                
                <p style="font-size: 10px; color: #495057; margin: 15px 0 0 0; border-top: 1px solid #495057; padding-top: 10px;">
                    Generated: {{ date('Y-m-d H:i:s') }} WIB | 
                    Document ID: {{ $display_number }}{{ date('Ymd') }} | 
                    @if($is_billing_email)
                        Type: CC Billing Email | Billing: {{ $billing_document }} | Delivery: {{ $delivery_order }}
                    @else
                        Type: CC Export Email | Delivery: {{ $delivery_order }} | Billing: Pending
                    @endif
                    | Recipients: {{ $total_recipients ?? 1 }} 
                    @if(isset($booking_number) && $booking_number)
                        | Booking: {{ $booking_number }}
                    @endif
                    | System: KMI Finance CC System v2.0
                </p>
            </div>
        </div>
    </div>
</body>
</html>