<?php

class BTX24{

    function method($method, $params){

        $url = 'your_link_for_rest_api' . $method;

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($c);
        $response = json_decode($response, true);

        return $response;
    }
}

