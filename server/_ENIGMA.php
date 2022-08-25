<?php

// ENIGMA SYSTEM | VANDALS BETA
//use Enigma;

include 'checkdb.php';

class Enigma
{
    private $cipher_algo;
    private $hash_algo;
    private $iv_num_bytes;

    public function __construct($cipher_algo = 'aes-256-cbc', $hash_algo = 'sha256')
    {
        $this->cipher_algo = $cipher_algo;
        $this->hash_algo = $hash_algo;

        if (!in_array($cipher_algo, openssl_get_cipher_methods(true))) {
            throw new \Exception("Enigma:: - unknown cipher algo {$cipher_algo}");
        }

        if (!in_array($hash_algo, openssl_get_md_methods(true))) {
            throw new \Exception("Enigma:: - unknown hash algo {$hash_algo}");
        }

        $this->iv_num_bytes = openssl_cipher_iv_length($cipher_algo);
    }

    // Encrypt a string
    public function EnigmaEncrypt($plaintext, $key) {

        // Build the random initialisation vector & some fluff
        $fluff = openssl_random_pseudo_bytes($this->iv_num_bytes);
        $iv = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        
        $init_value = openssl_random_pseudo_bytes($this->iv_num_bytes, $isStrongCrypto);
        
        if (!$isStrongCrypto) {
            throw new \Exception("Enigma::EnigmaEncrypt() - Not a strong key"); 
        }
        
        $iv = substr(hash($this->hash_algo, $init_value), 0, 16);

        // Encrypt plaintext
        //$opts =  OPENSSL_RAW_DATA;
        $opts = 0;
        $encrypted = openssl_encrypt($plaintext, $this->cipher_algo, $key, $opts, $iv);
        
        if ($encrypted === false) {
            throw new \Exception('Enigma::EnigmaEncrypt() - Encryption failed: ' . openssl_error_string()); }

        // The result comprises the IV and encrypted data
        $result = $encrypted . $init_value . $fluff;

        $result = base64_encode($result);
        
        return $result;
    }

    // Decrypt a string 
    public function EnigmaDecrypt($cipher, $key, $CheckDb = null){

        $key;

        $raw = base64_decode($cipher);

        // and do an integrity check on the size.
        if (strlen($raw) < $this->iv_num_bytes-16) {
            throw new \Exception("Request denied");
            die();
        }
        
        //Discard the fluff
        $relevant_length = strlen($raw)-32;
        
        // Extract the initialisation vector and encrypted data
        $init_value = substr($raw, $relevant_length, $this->iv_num_bytes);
        $msg = substr($raw, 0, $relevant_length);
        
        $iv = substr(hash($this->hash_algo, $init_value), 0, 16);
        
        if ($CheckDb == 1) {
        
            $status = CheckAgainst("$msg$iv");
        
            if ($status == 0) {

                // and decrypt.
                //$opts = OPENSSL_RAW_DATA;
                $opts = 0;
                $result = openssl_decrypt($msg, $this->cipher_algo, $key, $opts, $iv);
        
                if ($result === false) {
                    throw new \Exception('Error: ' . openssl_error_string() . "<br/>" . $iv);
                }
                
                return $result;
                
            } elseif ($status == 1) {
                
                die("Banned request !");
                
            }
        } else {
        
            // and decrypt.
            //$opts = OPENSSL_RAW_DATA;
            $opts = 0;
            $result = openssl_decrypt($msg, $this->cipher_algo, $key, $opts, $iv);
        
            if ($result === false) {
                throw new \Exception('Error: ' . openssl_error_string());
            }
        
            return $result;
            
        }
    }

    public static function Encrypt($in, $key){
        $c = new Enigma();
        return $c->EnigmaEncrypt($in, $key);
    }

    public static function Decrypt($in, $key, $CheckDb = null){
        $c = new Enigma();
        return $c->EnigmaDecrypt($in, $key, $CheckDb);
    }
    
}

?>