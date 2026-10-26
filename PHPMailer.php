<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $Host = 'smtp.gmail.com';
    public $Port = 465;
    public $Username = 'brianrubengb@gmail.com'; // Tu correo de Gmail
    public $Password = 'rmur aqja wwvj hpeh'; // Tu clave de aplicación de 16 dígitos
    public $FromName = 'Mi Tienda POS';

    private $to = [];
    public $Subject = '';
    public $Body = '';

    public function addAddress($address) {
        $this->to[] = $address;
    }

    public function send()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @fsockopen('ssl://' . $this->Host, $this->Port, $errno, $errstr, 15, $context);
        if (!$socket) {
            throw new \Exception("No se pudo conectar al servidor SMTP: $errstr ($errno)");
        }

        $this->read($socket);
        $this->write($socket, "EHLO " . gethostname());
        $this->write($socket, "AUTH LOGIN");
        $this->write($socket, base64_encode($this->Username));
        $this->write($socket, base64_encode($this->Password));

        $this->write($socket, "MAIL FROM: <" . $this->Username . ">");
        foreach ($this->to as $recipient) {
            $this->write($socket, "RCPT TO: <" . $recipient . ">");
        }

        $this->write($socket, "DATA");

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->FromName . " <" . $this->Username . ">\r\n";
        $headers .= "To: " . implode(',', $this->to) . "\r\n";
        $headers .= "Subject: " . $this->Subject . "\r\n";

        $message = $headers . "\r\n" . $this->Body . "\r\n.";
        $this->write($socket, $message);
        $this->write($socket, "QUIT");
        fclose($socket);

        return true;
    }

    private function write($socket, $cmd) {
        fputs($socket, $cmd . "\r\n");
        return $this->read($socket);
    }

    private function read($socket) {
        $res = '';
        while ($str = fgets($socket, 512)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
}