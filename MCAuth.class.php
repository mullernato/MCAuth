<?php

/*
 * class: MCAuth
 * description: Intergrate Minecraft in your PHP projects.
 * author: Mattia Basone
 * version: 1.1
 * info/support: mattia.basone@gmail.com
 */
class MCAuth {
    
    const CLIENT_TOKEN  = '808772fc24bc4d92ba2dc48bfecb375f';
    const AUTH_URL      = 'https://authserver.mojang.com/authenticate';
    const PROFILE_URL   = 'https://api.mojang.com/profiles/page/1';
    
    public $autherr, $account = array();
    private $curlresp, $curlinfo;
    
    // Generic function for cURL requests
    private function curl_request($address) {
        $request = curl_init();
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($request, CURLOPT_URL, $address);
        return curl_exec($request);
        curl_close($request);
    }
    
    //  Execute a POST request with JSON data
    private function curl_json($url, $array) {
        $request = curl_init();
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_HTTPHEADER , array('Content-Type: application/json'));
        curl_setopt($request, CURLOPT_POST, TRUE);
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($array));
        curl_setopt($request, CURLOPT_URL, $url);
        $response = curl_exec($request);
        $this->curlinfo = curl_getinfo($request);
        curl_close($request);
        $rjson = json_decode($response);
        if ($this->curlinfo['http_code'] == '200') {
            $this->curlresp = $rjson;
            return TRUE;
        } else {
            $this->curlresp = NULL;
            return FALSE;
        }
    }
    
    // Allowed characters for username
    private function check_username($username) {
        if (preg_match('#[^a-zA-Z0-9_]+#', $username)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    // Check if username is premium
    public function check_pemium($username) {
        if ($this->curl_request('https://www.minecraft.net/haspaid.jsp?user='.$username)  == 'true') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    // Authentication
    public function authenticate($username, $password) {
        // json array for POST authentication
        $json = array();
        $json['agent']['name'] = 'Minecraft';
        $json['agent']['version'] = 1;
        $json['username'] = $username;
        $json['password'] = $password;
        $json['clientToken'] = self::CLIENT_TOKEN;
        if ($this->curl_json(self::AUTH_URL, $json) === TRUE) {
            if (!isset($this->curlresp->error) AND isset($this->curlresp->selectedProfile->name)) {
                $this->account['id'] = $this->curlresp->selectedProfile->id;
                $this->account['username'] = $this->curlresp->selectedProfile->name;
                $this->account['token'] = $this->curlresp->accessToken;
                $this->autherr = 'OK';
                return TRUE;
            } else {
                $this->autherr = $this->curlresp->errorMessage;
                return FALSE;
            }
        } else {
            if (isset($this->curlresp->error)) {
                $this->autherr = $this->curlresp->errorMessage;
                return FALSE;
            } else {
                if (isset($this->curlinfo['http_code'])) {
                    $this->autherr = 'Server returned http code '.$this->curlinfo['http_code'];
                } else {
                    $this->autherr = 'Server unreacheable';
                }
                return FALSE;
            }
        }
    }
    
    // Get correct username and minecraft id from username (NOT email, case insensitive)
    public function get_user_info($username) {
        if ($this->check_username($username) === TRUE) {
            $p_array['agent'] = 'Minecraft';
            $p_array['name'] = $username;
            if ($this->curl_json(self::PROFILE_URL, $p_array) === TRUE) {
                if (isset($this->curlresp->profiles[0]->name)) {
                    $this->account['id'] = $this->curlresp->profiles[0]->id;
                    $this->account['username'] = $this->curlresp->profiles[0]->name;
                    return TRUE;
                }
                else { 
                    return FALSE; 
                }
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
}