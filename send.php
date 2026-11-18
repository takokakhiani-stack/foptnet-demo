<?php
/**
 * FOPTNET — ფორმების გამგზავნი (მაგალითი).
 * საიტი აგზავნის JSON-ს POST-ით. ეს ფაილი იღებს მას და აგზავნის ელ. ფოსტაზე.
 * გამოსაყენებლად index.html-ში ჩაწერეთ: formEndpoint: 'send.php'
 *
 * IT: შეცვალეთ მხოლოდ ქვემოთ მოცემული 2 ხაზი (მიმღები და გამგზავნი).
 * თუ სერვერზე mail() არ მუშაობს, გამოიყენეთ SMTP (მაგ. PHPMailer), ან შეცვალეთ ეს ფაილი
 * თქვენთვის სასურველი ენით/სისტემით (Node, Python, Formspree და ა.შ.) — საიტს მხოლოდ HTTP 200 პასუხი სჭირდება.
 */

$TO   = 'sales@foptnet.ge';        // ← ვის მიუვიდეს მოთხოვნები (შეიძლება რამდენიმე, მძიმით)
$FROM = 'no-reply@foptnet.ge';     // ← გამგზავნი მისამართი (უნდა იყოს თქვენი დომენის)

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }

$raw = file_get_contents('php://input');
if (strlen($raw) > 20000) { http_response_code(413); echo '{"ok":false}'; exit; }
$d = json_decode($raw, true);
if (!is_array($d)) { http_response_code(400); echo '{"ok":false}'; exit; }

function v($a, $k) { $x = isset($a[$k]) ? $a[$k] : ''; if (is_array($x)) $x = implode(', ', $x); return trim(str_replace(["\r", "\n"], ' ', (string)$x)); }

$email = v($d, 'email');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo '{"ok":false}'; exit; }

$labels = [
  'form' => 'ფორმა', 'name' => 'სახელი', 'firstName' => 'სახელი', 'lastName' => 'გვარი',
  'company' => 'კომპანია', 'email' => 'ელ. ფოსტა', 'phone' => 'ტელეფონი',
  'service' => 'სერვისი', 'capacity' => 'გამტარობა', 'bandwidth' => 'გამტარობა',
  'pointA' => 'წერტილი A', 'pointB' => 'წერტილი B', 'requestType' => 'მოთხოვნის ტიპი',
  'message' => 'შეტყობინება', 'consent' => 'თანხმობა', 'lang' => 'ენა', 'page' => 'გვერდი', 'sentAt' => 'დრო',
];
$body = '';
foreach ($labels as $k => $l) {
  if (!isset($d[$k])) continue;
  $val = $k === 'message' ? trim((string)$d[$k]) : v($d, $k);
  if ($k === 'consent') $val = $d[$k] ? 'კი' : 'არა';
  if ($val === '') continue;
  $body .= $l . ': ' . $val . "\n";
}

$type = v($d, 'form') === 'enquiry' ? 'რუკიდან შეთავაზება' : 'საკონტაქტო ფორმა';
$who  = v($d, 'company') ?: v($d, 'name');
$subject = '=?UTF-8?B?' . base64_encode("FOPTNET — $type: $who") . '?=';
$headers = "From: FOPTNET <$FROM>\r\nReply-To: $email\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$ok = mail($TO, $subject, $body, $headers);
http_response_code($ok ? 200 : 500);
echo json_encode(['ok' => $ok]);
