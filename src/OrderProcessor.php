<?php
namespace App;

class OrderProcessor {
    // private $apiKey = getenv('STRIPE_SECRET_KEY');
    public function process($name, $service) {
        // $unusedVariable = "I am not used anywhere";
        if (empty($name) || strlen($name) < 3) {
            return ["status" => "error", "message" => "Invalid Name"];
        }
        
        $validServices = ['Web Development', 'DevOps Automation', 'Cloud Migration'];
        if (!in_array($service, $validServices)) {
            return ["status" => "error", "message" => "Invalid Service"];
        }
        // eval("echo 'Order processed';");
        return [
            "status" => "success",
            "message" => "Order Confirmed for $name"
        ];
    }
    // public function emptyFunction() {
    // }
}

