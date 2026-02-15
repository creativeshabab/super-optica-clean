<?php
/**
 * Integration Manager
 * 
 * Central service for managing all third-party integrations
 * (payment gateways, shipping services, email, SMS, analytics)
 */

class IntegrationManager {
    private $pdo;
    private $encryptionKey;
    private static $instance = null;
    
    /**
     * Singleton instance
     */
    public static function getInstance($pdo) {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }
    
    private function __construct($pdo) {
        $this->pdo = $pdo;
        // Use a secure encryption key (should be stored in environment variable in production)
        $this->encryptionKey = defined('INTEGRATION_ENCRYPTION_KEY') 
            ? INTEGRATION_ENCRYPTION_KEY 
            : 'default-key-change-in-production-12345678';
    }
    
    /**
     * Encrypt sensitive data (API keys, secrets)
     */
    private function encrypt($data) {
        if (empty($data)) return '';
        
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    private function decrypt($data) {
        if (empty($data)) return '';
        
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );
    }
    
    /**
     * Save integration configuration
     */
    public function saveIntegration($serviceName, $serviceType, $config, $options = []) {
        try {
            // Encrypt the config data
            $encryptedConfig = $this->encrypt(json_encode($config));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO service_integrations 
                (service_name, service_type, config_data, is_enabled, is_test_mode, display_name, logo_url, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    config_data = VALUES(config_data),
                    is_enabled = VALUES(is_enabled),
                    is_test_mode = VALUES(is_test_mode),
                    display_name = VALUES(display_name),
                    logo_url = VALUES(logo_url),
                    display_order = VALUES(display_order),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $serviceName,
                $serviceType,
                $encryptedConfig,
                $options['is_enabled'] ?? 0,
                $options['is_test_mode'] ?? 1,
                $options['display_name'] ?? ucfirst($serviceName),
                $options['logo_url'] ?? '',
                $options['display_order'] ?? 0
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Integration save error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get integration configuration
     */
    public function getIntegration($serviceName) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM service_integrations 
                WHERE service_name = ? 
                LIMIT 1
            ");
            $stmt->execute([$serviceName]);
            $integration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($integration && !empty($integration['config_data'])) {
                $integration['config'] = json_decode($this->decrypt($integration['config_data']), true);
                unset($integration['config_data']); // Remove encrypted data from output
            }
            
            return $integration;
        } catch (PDOException $e) {
            error_log("Integration fetch error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all integrations by type
     */
    public function getIntegrationsByType($serviceType) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, service_name, service_type, is_enabled, is_test_mode, 
                       display_name, logo_url, display_order, created_at, updated_at
                FROM service_integrations 
                WHERE service_type = ?
                ORDER BY display_order ASC, display_name ASC
            ");
            $stmt->execute([$serviceType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Integration list error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get enabled payment gateways
     */
    public function getEnabledPaymentGateways($testMode = null) {
        try {
            $sql = "
                SELECT * FROM service_integrations 
                WHERE service_type = 'payment' AND is_enabled = 1
            ";
            
            if ($testMode !== null) {
                $sql .= " AND is_test_mode = " . ($testMode ? '1' : '0');
            }
            
            $sql .= " ORDER BY display_order ASC";
            
            $stmt = $this->pdo->query($sql);
            $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decrypt config for each gateway
            foreach ($gateways as &$gateway) {
                if (!empty($gateway['config_data'])) {
                    $gateway['config'] = json_decode($this->decrypt($gateway['config_data']), true);
                    unset($gateway['config_data']);
                }
            }
            
            return $gateways;
        } catch (PDOException $e) {
            error_log("Payment gateways fetch error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toggle integration enabled status
     */
    public function toggleIntegration($serviceName, $enabled) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE service_integrations 
                SET is_enabled = ?, updated_at = CURRENT_TIMESTAMP
                WHERE service_name = ?
            ");
            return $stmt->execute([$enabled ? 1 : 0, $serviceName]);
        } catch (PDOException $e) {
            error_log("Integration toggle error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle test mode
     */
    public function toggleTestMode($serviceName, $testMode) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE service_integrations 
                SET is_test_mode = ?, updated_at = CURRENT_TIMESTAMP
                WHERE service_name = ?
            ");
            return $stmt->execute([$testMode ? 1 : 0, $serviceName]);
        } catch (PDOException $e) {
            error_log("Test mode toggle error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test connection to a service
     */
    public function testConnection($serviceName) {
        $integration = $this->getIntegration($serviceName);
        
        if (!$integration || empty($integration['config'])) {
            return ['success' => false, 'message' => 'Integration not configured'];
        }
        
        // Service-specific connection tests
        switch ($serviceName) {
            case 'razorpay':
                return $this->testRazorpayConnection($integration['config']);
            
            case 'shiprocket':
                return $this->testShiprocketConnection($integration['config']);
            
            default:
                return ['success' => false, 'message' => 'Connection test not implemented for this service'];
        }
    }
    
    /**
     * Test Razorpay connection
     */
    private function testRazorpayConnection($config) {
        if (empty($config['key_id']) || empty($config['key_secret'])) {
            return ['success' => false, 'message' => 'API credentials are missing'];
        }
        
        try {
            require_once __DIR__ . '/razorpay/RazorpayAPI.php';
            $razorpay = new RazorpayAPI($config['key_id'], $config['key_secret']);
            
            // Try to create a test order for Re 1
            $result = $razorpay->createOrder(1, 'INR', ['test' => 'connection']);
            
            if (isset($result['id'])) {
                return ['success' => true, 'message' => 'Connection successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid API response'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test Shiprocket connection
     */
    private function testShiprocketConnection($config) {
        if (empty($config['email']) || empty($config['password'])) {
            return ['success' => false, 'message' => 'Email or password is missing'];
        }
        
        try {
            require_once __DIR__ . '/shiprocket/ShiprocketAPI.php';
            $shiprocket = new ShiprocketAPI($config['email'], $config['password']);
            
            // Try to authenticate
            $token = $shiprocket->authenticate();
            
            if ($token) {
                return ['success' => true, 'message' => 'Connection successful'];
            } else {
                return ['success' => false, 'message' => 'Authentication failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get all shipping methods
     */
    public function getShippingMethods($enabledOnly = false) {
        try {
            $sql = "SELECT * FROM shipping_methods";
            if ($enabledOnly) {
                $sql .= " WHERE is_enabled = 1";
            }
            $sql .= " ORDER BY base_cost ASC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Shipping methods fetch error: " . $e->getMessage());
            return [];
        }
    }
}
?>
