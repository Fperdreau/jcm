<?php
/**
 * File for class AppMail
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

use PHPMailer\PHPMailer\PHPMailer;
use Html2Text;

/**
 * Class Mail
 *
 * requirement: PHPMailer, HTML2TEXT
 */
class Mail
{

    /**
     * @var array $settings
     */
    private $settings = array(
        'mail_from'=>null,
        'mail_from_name'=>null,
        'mail_host'=>null,
        'mail_port'=>null,
        'mail_username'=>null,
        'mail_password'=>null,
        'SMTP_secure'=>"ssl",
        'SMTP_debug'=>1
    );

    /**
     * @var string $logs: Logs
     */
    private $logs;

    /**
     * @var PHPMailer $PhpMailer: PhpMailer instance
     */
    private static $PhpMailer;

    /**
     * Class constructor
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->setSettings($settings);
    }

    /**
     * Set settings
     * @param $settings
     * @return array
     */
    public function setSettings($settings)
    {
        if (isset($settings['SMTP_debug'])) {
            if ($settings['SMTP_debug'] === 'false') {
                $settings['SMTP_debug'] = false;
            } elseif ($settings['SMTP_debug'] === 'true') {
                $settings['SMTP_debug'] = true;
            } else {
                $settings['SMTP_debug'] = (int)$settings['SMTP_debug'];
            }
        }

        foreach ($settings as $key => $value) {
            if (in_array($key, array_keys($this->settings))) {
                $this->settings[$key] = $value;
            }
        }

        return $this->settings;
    }

    /**
     * Get PhpMailer instance
     *
     * @return PHPMailer
     */
    private function getPhpMailer()
    {
        if (is_null(self::$PhpMailer)) {
            self::$PhpMailer = new PHPMailer(false);
        }
        return self::$PhpMailer;
    }

    /**
     * Test if domain email exists
     *
     * @param $email
     * @param string $record
     * @return bool
     */
    public static function domainExists($email, $record = 'MX')
    {
        $split = explode('@', $email);
        return count($split)<2 ? false : checkdnsrr($split[1], $record);
    }

    /**
     * Send an email
     * @param array|string $to : recipients list
     * @param string $subject : email title
     * @param string $body : body text
     * @param array|null $attachment : list of attached files
     * @param bool $undisclosed : hide (true) recipients list
     * @return array : array(
     *                      'status'=>bool (success or failure),
     *                      'logs'=>string (Error or message)
     *                 )
     *
     * @throws phpmailerException
     * @throws Exception
     */
    public function send($to, $subject, $body, $attachment = null, $undisclosed = true)
    {
        if (!is_array($to)) {
            $to = array($to);
        }

        try {
            // Set PhpMailer instance
            $mail = $this->setMailObj($subject, $body);

            // Add recipients
            foreach ($to as $to_add) {
                if (!self::domainExists($to_add)) {
                    return array(
                        'status' => false,
                        'logs' => "{$to_add} is not a valid email address"
                    );
                }

                if ($undisclosed) {
                    if (!$mail->addBCC($to_add)) {
                        Logger::getInstance('jcm')->error($mail->ErrorInfo);
                        return array('status' => false, 'logs' => $mail->ErrorInfo);
                    }
                } else {
                    if (!$mail->addAddress($to_add)) {
                        Logger::getInstance('jcm')->error($mail->ErrorInfo);
                        return array('status' => false, 'logs' => $mail->ErrorInfo);
                    }
                }
            }

            // Add attachments
            if (!is_null($attachment)) {
                if (!is_array($attachment)) {
                    $attachment = array($attachment);
                }
                foreach ($attachment as $path) {
                    $split = explode('/', $path);
                    $file_name = end($split);
                    if (!$mail->addAttachment($path, $file_name)) {
                        Logger::getInstance('jcm')->error($mail->ErrorInfo);
                        return array('status' => false, 'logs' => $mail->ErrorInfo);
                    }
                }
            }

            // Send email
            if (!$mail->send()) {
                Logger::getInstance('jcm')->error($mail->ErrorInfo);
                return array('status' => false, 'logs' => $mail->ErrorInfo);
            } else {
                $mail->clearAddresses();
                $mail->clearAttachments();
                return array('status' => true, 'logs' => $this->logs);
            }
        } catch (Exception $e) {
            Logger::getInstance('jcm')->error($e->getMessage());
            return array('status' => false, 'logs' => $e->getMessage());
        }
    }

    /**
     * Set PhpMailer object
     *
     * @param string $subject: email subject
     * @param string $body: email content
     * @return PhpMailer
     */
    private function setMailObj($subject, $body)
    {
        // Get PhpMailer instance
        $mail = self::getPhpMailer();

        $mail->CharSet = 'UTF-8';
        $mail->Mailer = "smtp";
        $mail->isSMTP();                                      // set mailer to use SMTP

        // Settings
        $mail->WordWrap = 50;                                 // set word wrap to 50 characters

        // SMTP information
        $mail->SMTPDebug = $this->settings['SMTP_debug'];     // enables SMTP debug information (for testing)
        $mail->Debugoutput = 'html'; //Ask for HTML-friendly debug output
        $mail->Host = $this->settings['mail_host'];
        $mail->Port = $this->settings['mail_port'];

        // Set sender
        $mail->setFrom($this->settings['mail_from'], $this->settings['mail_from_name']);

        // Set SMTP authentication
        if ($this->settings['SMTP_secure'] !== "none") {
            $mail->SMTPAuth = true;     // turn on SMTP authentication
            $mail->SMTPSecure = $this->settings['SMTP_secure']; // secure transfer enabled REQUIRED for GMail
            $mail->Username = $this->settings['mail_username'];
            $mail->Password = $this->settings['mail_password'];
        }

        // Debugger output
        $mail->Debugoutput = function ($str, $level) {
            $this->logs .= "$level: $str\n";
        };

        // Mail content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        // Convert to plain text for email viewers non-compatible with HTML content
        $mail->AltBody = Html2Text\Html2Text::convert($body, true);

        // Add recipients
        $mail->addReplyTo($mail->From, $mail->FromName);

        return $mail;
    }
}
