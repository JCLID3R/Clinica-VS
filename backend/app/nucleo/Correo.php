<?php
/**
 * Correo: envío de correos con la API HTTP de Brevo (Render bloquea SMTP, por eso no se usa mail()).
 *   Correo::enviar('usuario@correo.com', 'Asunto', '<p>Contenido</p>');
 *
 * Variables del .env: MAIL_API_KEY, MAIL_REMITENTE, MAIL_REMITENTE_NOMBRE.
 * Sin MAIL_API_KEY en desarrollo, el correo se escribe en el log de errores de PHP.
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Correo
{
    private const URL_API = 'https://api.brevo.com/v3/smtp/email';

    /** Envía un correo HTML. Devuelve true si la API lo aceptó (o si se escribió en el log en desarrollo). */
    public static function enviar(string $para, string $asunto, string $html): bool
    {
        $clave = (string) Config::obtener('MAIL_API_KEY', '');
        if ($clave === '') {
            if (Config::esDesarrollo()) {
                error_log("Correo (desarrollo, sin MAIL_API_KEY)\nPara: {$para}\nAsunto: {$asunto}\n{$html}");
                return true;
            }
            error_log('Correo: falta MAIL_API_KEY; no se envió el correo a ' . $para);
            return false;
        }
        if (!function_exists('curl_init')) {
            error_log('Correo: la extensión curl no está habilitada.');
            return false;
        }

        $cuerpo = json_encode([
            'sender'      => [
                'email' => (string) Config::obtener('MAIL_REMITENTE', ''),
                'name'  => (string) Config::obtener('MAIL_REMITENTE_NOMBRE', 'Clínica Vida Salud'),
            ],
            'to'          => [['email' => $para]],
            'subject'     => $asunto,
            'htmlContent' => $html,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::URL_API);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $clave,
            ],
        ]);
        $respuesta = curl_exec($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false || $estado < 200 || $estado >= 300) {
            error_log("Correo: Brevo respondió {$estado} al enviar a {$para}. {$error} " . (is_string($respuesta) ? $respuesta : ''));
            return false;
        }
        return true;
    }
}
