<?php
/**
 * Shiprocket API Wrapper
 * Comprehensive integration for Shiprocket shipping and logistics services
 */

class ShiprocketAPI {
    private $email;
    private $password;
    private $baseUrl = 'https://apiv2.shiprocket.in/v1/external/';
    private $token = null;
    private $tokenExpiry = null;
    
    public function __construct($email, $password) {
        $this->email = $email;
        $this->password = $password;
    }
    
    /**
     * Authenticate and get access token
     */
    public function authenticate() {
        // Check if token is still valid
        if ($this->token && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->token;
        }
        
        $data = [
            'email' => $this->email,
            'password' => $this->password
        ];
        
        $response = $this->makeRequest('auth/login', 'POST', $data, false);
        
        if (isset($response['token'])) {
            $this->token = $response['token'];
            // Token typically valid for 10 days, but we'll refresh after 9 days to be safe
            $this->tokenExpiry = time() + (9 * 24 * 60 * 60);
            return $this->token;
        }
        
        throw new Exception('Shiprocket authentication failed');
    }
    
    /**
     * Create an order in Shiprocket
     * 
     * @param array $orderData Order details including products, customer info, shipping address
     * @return array Shiprocket order response
     */
    public function createOrder($orderData) {
        $this->authenticate();
        
        $payload = [
            'order_id' => $orderData['order_id'],
            'order_date' => $orderData['order_date'] ?? date('Y-m-d H:i'),
            'pickup_location' => $orderData['pickup_location'] ?? 'Primary',
            'channel_id' => '',
            'comment' => $orderData['comment'] ?? '',
            'billing_customer_name' => $orderData['billing_customer_name'],
            'billing_last_name' => $orderData['billing_last_name'] ?? '',
            'billing_address' => $orderData['billing_address'],
            'billing_address_2' => $orderData['billing_address_2'] ?? '',
            'billing_city' => $orderData['billing_city'],
            'billing_pincode' => $orderData['billing_pincode'],
            'billing_state' => $orderData['billing_state'],
            'billing_country' => $orderData['billing_country'] ?? 'India',
            'billing_email' => $orderData['billing_email'],
            'billing_phone' => $orderData['billing_phone'],
            'shipping_is_billing' => $orderData['shipping_is_billing'] ?? true,
            'shipping_customer_name' => $orderData['shipping_customer_name'] ?? $orderData['billing_customer_name'],
            'shipping_last_name' => $orderData['shipping_last_name'] ?? ($orderData['billing_last_name'] ?? ''),
            'shipping_address' => $orderData['shipping_address'] ?? $orderData['billing_address'],
            'shipping_address_2' => $orderData['shipping_address_2'] ?? ($orderData['billing_address_2'] ?? ''),
            'shipping_city' => $orderData['shipping_city'] ?? $orderData['billing_city'],
            'shipping_pincode' => $orderData['shipping_pincode'] ?? $orderData['billing_pincode'],
            'shipping_country' => $orderData['shipping_country'] ?? ($orderData['billing_country'] ?? 'India'),
            'shipping_state' => $orderData['shipping_state'] ?? $orderData['billing_state'],
            'shipping_email' => $orderData['shipping_email'] ?? $orderData['billing_email'],
            'shipping_phone' => $orderData['shipping_phone'] ?? $orderData['billing_phone'],
            'order_items' => $orderData['order_items'],
            'payment_method' => $orderData['payment_method'] ?? 'Prepaid',
            'shipping_charges' => $orderData['shipping_charges'] ?? 0,
            'giftwrap_charges' => $orderData['giftwrap_charges'] ?? 0,
            'transaction_charges' => $orderData['transaction_charges'] ?? 0,
            'total_discount' => $orderData['total_discount'] ?? 0,
            'sub_total' => $orderData['sub_total'],
            'length' => $orderData['length'] ?? 10,
            'breadth' => $orderData['breadth'] ?? 10,
            'height' => $orderData['height'] ?? 10,
            'weight' => $orderData['weight'] ?? 0.5
        ];
        
        return $this->makeRequest('orders/create/adhoc', 'POST', $payload);
    }
    
    /**
     * Generate AWB (Air Waybill) for shipment
     */
    public function generateAWB($shipmentId, $courierId = null) {
        $this->authenticate();
        
        $data = ['shipment_id' => $shipmentId];
        if ($courierId) {
            $data['courier_id'] = $courierId;
        }
        
        return $this->makeRequest('courier/assign/awb', 'POST', $data);
    }
    
    /**
     * Get available courier services for a shipment
     */
    public function getCourierServiceability($pickupPostcode, $deliveryPostcode, $weight, $codAmount = 0) {
        $this->authenticate();
        
        $params = [
            'pickup_postcode' => $pickupPostcode,
            'delivery_postcode' => $deliveryPostcode,
            'weight' => $weight,
            'cod' => $codAmount > 0 ? 1 : 0
        ];
        
        return $this->makeRequest('courier/serviceability?' . http_build_query($params), 'GET');
    }
    
    /**
     * Request pickup for shipments
     */
    public function requestPickup($shipmentIds) {
        $this->authenticate();
        
        $data = ['shipment_id' => is_array($shipmentIds) ? $shipmentIds : [$shipmentIds]];
        
        return $this->makeRequest('courier/generate/pickup', 'POST', $data);
    }
    
    /**
     * Track shipment by AWB or Shipment ID
     */
    public function trackShipment($shipmentId) {
        $this->authenticate();
        
        return $this->makeRequest('courier/track/shipment/' . $shipmentId, 'GET');
    }
    
    /**
     * Cancel shipment
     */
    public function cancelShipment($orderIds) {
        $this->authenticate();
        
        $data = ['ids' => is_array($orderIds) ? $orderIds : [$orderIds]];
        
        return $this->makeRequest('orders/cancel', 'POST', $data);
    }
    
    /**
     * Get order details
     */
    public function getOrder($orderId) {
        $this->authenticate();
        
        return $this->makeRequest('orders/show/' . $orderId, 'GET');
    }
    
    /**
     * Get all pickup locations/warehouses
     */
    public function getPickupLocations() {
        $this->authenticate();
        
        return $this->makeRequest('settings/company/pickup', 'GET');
    }
    
    /**
     * Add pickup location
     */
    public function addPickupLocation($locationData) {
        $this->authenticate();
        
        $payload = [
            'pickup_location' => $locationData['name'],
            'name' => $locationData['contact_name'],
            'email' => $locationData['email'],
            'phone' => $locationData['phone'],
            'address' => $locationData['address'],
            'address_2' => $locationData['address_2'] ?? '',
            'city' => $locationData['city'],
            'state' => $locationData['state'],
            'country' => $locationData['country'] ?? 'India',
            'pin_code' => $locationData['pin_code']
        ];
        
        return $this->makeRequest('settings/company/addpickup', 'POST', $payload);
    }
    
    /**
     * Calculate shipping rates
     */
    public function calculateRates($pickupPostcode, $deliveryPostcode, $weight, $cod = 0, $declaredValue = 0) {
        $this->authenticate();
        
        $params = [
            'pickup_postcode' => $pickupPostcode,
            'delivery_postcode' => $deliveryPostcode,
            'weight' => $weight,
            'cod' => $cod,
            'declared_value' => $declaredValue
        ];
        
        return $this->makeRequest('courier/serviceability?' . http_build_query($params), 'GET');
    }
    
    /**
     * Create a shipment for a specific order (if using non-adhoc flow)
     */
    public function createShipment($orderId) {
        $this->authenticate();
        
        $data = ['order_id' => $orderId];
        
        return $this->makeRequest('orders/create/shipment', 'POST', $data);
    }

    /**
     * Generate Manifest
     */
    public function generateManifest($shipmentIds) {
        $this->authenticate();
        
        $data = ['shipment_id' => is_array($shipmentIds) ? $shipmentIds : [$shipmentIds]];
        
        return $this->makeRequest('manifests/generate', 'POST', $data);
    }

    /**
     * Print Manifest
     */
    public function printManifest($orderIds) {
        $this->authenticate();
        
        $data = ['order_ids' => is_array($orderIds) ? $orderIds : [$orderIds]];
        
        return $this->makeRequest('manifests/print', 'POST', $data);
    }
    
    /**
     * Make HTTP request to Shiprocket API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = [], $requireAuth = true) {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        
        $headers = ['Content-Type: application/json'];
        
        if ($requireAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET') {
             curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
             // Handle Shiprocket specific error structure
             $msg = 'Unknown error';
             if (isset($result['message'])) $msg = $result['message'];
             if (isset($result['errors'])) $msg .= ' - ' . json_encode($result['errors']);
             
             throw new Exception('Shiprocket API Error (' . $httpCode . '): ' . $msg);
        }
        
        return $result;
    }
}
?>
