<?php

////////////////////////////////////////////////////////////////////////////////
// Instellingen
// De variabelen hieronder kunnen naar wens aangepast worden
////////////////////////////////////////////////////////////////////////////////

// URL naar het bestelformulier
$formurl = "../pages/form.html";

// URL naar de 'bedankt'-pagina
$thanksurl = "../pages/thanks.html";

// Mailadres waarnaartoe het bericht verstuurd moet worden
$emailaddress = "akshayngp@yahoo.com";

// De verplichte velden
$required = array("Naam", "Bedrijfsnaam", "Telefoonnumer", "E-mail");

////////////////////////////////////////////////////////////////////////////////
// Hieronder is de werkelijke programmatuur; wees erg voorzichtig met aanpassen
// van deze functionaliteit, tenzij je verstand van zaken hebt.
////////////////////////////////////////////////////////////////////////////////

// uniek nummer -> bestelnummer
$uid = strtoupper(uniqid(""));

// controleer of het verplichte veld is ingevuld
function isvalid($fieldname) {
  $valid = false;

  if(isset($_POST[$fieldname]) && !empty($_POST[$fieldname])) {
    $valid = true;
  }

  return $valid;
}

function checkrequired() {
  global $required;

  $valid = true;
  foreach($required as $i => $field) {
    if(!isvalid($field)) {
      // veld is niet goed ingevuld
      $valid = false;
      break;
    }
  }

  return $valid;
}

// verstuur de bestelling-mail
function sendmail($to) {
  global $uid;
  $subject = "Bestelling: " . $uid;

  $html  = "<html><body>";
  $html .= "<h1>Bestelling " . $uid . "</h1>";
  $html .= "<p>Hieronder vindt u het ingevulde bestelformulier:</p>";

  foreach ($_POST as $key => $value) {
    if(!empty($value)) {
      $html .= "<p><strong>" . $key . ":</strong><br />" . nl2br(htmlentities($value)) . "</p>";
    }
  }

  $html .= "</body></html>";

  return multipartmail($_POST["Naam"], $_POST["E-mail"], $to, $subject, $html);
}

// verstuur het 'bedankt'-mailtje
function sendthanks($to) {
  global $emailaddress, $uid;
  $subject = "Bedankt voor uw bestelling";

  $html  = "<html><body>";
  $html .= "<h1>Bestelling verzonden</h1>";
  $html .= "<p>Uw bestelling (nr. " . $uid . ") is verzonden naar " . $emailaddress . ", waarvoor dank.</p>";
  $html .= "<p>M.v.g.,</p><p>Afzender<br />" . $emailaddress . "</p>";

  return multipartmail("Mediterranee Food", $emailaddress, $to, $subject, $html);
}

// multipart mail versturen
function multipartmail($name, $from, $to, $subject, $html) {
  $headers  = "From: \"" . strip_tags($name) . "\" <" . strip_tags($from) . ">\n";
  $headers .= "Reply-To: " . strip_tags($from) . "\n";

  $boundary = md5(date('r', time()));

  $headers .= "Content-Type: multipart/alternative;";
  $headers .= " boundary=\"phpMailer-" . $boundary . "\"\n";
  $headers .= "MIME-Version: 1.0\n";
  $headers .= "This is a MIME encoded message.\n";

  // plain text part
  $body  = "--phpMailer-" . $boundary . "\n";
  $body .= "Content-Type: text/plain; charset=\"ISO-8859-1\"\n";
  $body .= "Content-Transfer-Encoding: base64\r\n";
  $body .= chunk_split(base64_encode(strip_tags(preg_replace('/<br(\s+)?\/?>/i', "\n", preg_replace('/<p>/i', "\n\n", $html)))));

  // html part
  $body .= "--phpMailer-" . $boundary . "\n";
  $body .= "Content-Type: text/html; charset=\"ISO-8859-1\"\n";
  $body .= "Content-Transfer-Encoding: base64\r\n";
  $body .= chunk_split(base64_encode($html));

  $body .= "\n--phpMailer-" . $boundary . "--";

  return mail($to, $subject, $body, $headers);
}

// redirect de pagina naar de opgegeven URL
function redirect($url) {
  header("Location: " . $url);
}

// handel het formulier netjes af en voor de acties correct uit
function handle() {
  global $formurl, $emailaddress, $thanksurl;

  // controleer eerst de verplichte velden
  if(checkrequired()) {
    // probeer de mails te versturen
    if (sendmail($emailaddress) && sendthanks($_POST["E-mail"])) {
      // redirect naar het bedankje
      redirect($thanksurl);
    } else {
      // het mailen gaat mis; redirect naar formulier met deze parameter
      redirect($formurl . "?error=mail-error");
    }
  }
  else {
    redirect($formurl . "?error=invalid");
  }
}

// do it!
handle();

?>