<?php
declare(strict_types=1);
ini_set('session.use_strict_mode','1');
$secure=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
session_set_cookie_params(['secure'=>$secure,'httponly'=>true,'samesite'=>'Lax','path'=>'/']);
session_start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
if(empty($_SESSION['quote_token'])){$_SESSION['quote_token']=bin2hex(random_bytes(32));$_SESSION['quote_started']=time();}
function h(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$names=['naam','telefoon','email','dienst','voertuig','ophaalplaats','bestemming','datum','bericht'];
$values=array_fill_keys($names,'');$errors=[];$sent=false;
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 foreach($names as $name){$v=$_POST[$name]??'';$values[$name]=is_string($v)?trim($v):'';if(strlen($values[$name])>4000){$errors[]='Een invoerveld is te lang.';}}
 $token=$_POST['token']??'';
 if(!is_string($token)||!hash_equals($_SESSION['quote_token'],$token)){$errors[]='Je sessie is verlopen. Vernieuw de pagina en probeer opnieuw.';}
 if(!empty($_POST['website'])||time()-(int)($_SESSION['quote_started']??time())<3){$errors[]='De aanvraag kon niet worden verwerkt. Probeer opnieuw of bel ACR.';}
 if(time()-(int)($_SESSION['quote_last']??0)<60){$errors[]='Wacht even voor je opnieuw een aanvraag verstuurt.';}
 if(strlen($values['naam'])<2){$errors[]='Vul je naam in.';}
 if(!preg_match('/^[+0-9() .-]{7,30}$/D',$values['telefoon'])){$errors[]='Vul een geldig telefoonnummer in.';}
 if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL)||strpbrk($values['email'],"\r\n")!==false){$errors[]='Vul een geldig e-mailadres in.';}
 if($values['ophaalplaats']===''){$errors[]='Vul de ophaalplaats in.';}
 if($values['bericht']===''){$errors[]='Beschrijf je aanvraag.';}
 if(($_POST['privacy']??'')!=='1'){$errors[]='Bevestig dat ACR je mag contacteren over deze aanvraag.';}
 $allowed=['Depannage / takeldienst','Voertuigtransport','Lostrekken','Motortransport','Camper / caravan','Veilingtransport','Andere aanvraag'];
 if(!in_array($values['dienst'],$allowed,true)){$errors[]='Kies een dienst uit de lijst.';}
 if(!$errors){
  $text="Nieuwe offerteaanvraag via depannageoostvlaanderen.be\n\n";
  foreach($values as $key=>$value){$text.=ucfirst($key).": ".$value."\n\n";}
  $headers=['From'=>'Takeldienst ACR <website@depannageoostvlaanderen.be>','Reply-To'=>$values['email'],'MIME-Version'=>'1.0','Content-Type'=>'text/plain; charset=UTF-8'];
  if(mail('depannageacr@hotmail.com','Offerteaanvraag - Depannage Oost-Vlaanderen ACR',$text,$headers)){
   $_SESSION['quote_last']=time();$_SESSION['quote_sent']=true;unset($_SESSION['quote_token']);
   header('Location: '.(str_starts_with($_SERVER['REQUEST_URI']??'','/contact/')?'/contact/':'/offerte/').'?verzonden=1',true,303);exit;
  }else{$errors[]='Verzenden is niet gelukt. Bel 0471 37 90 64 of mail depannageacr@hotmail.com. Je aanvraag is niet bevestigd.';}
 }
 http_response_code($errors?422:200);
}
$sent=isset($_GET['verzonden'])&&!empty($_SESSION['quote_sent']);
if($sent){unset($_SESSION['quote_sent']);}
ob_start();
?>
<?php if($sent): ?>
<div class="notice" role="status"><h2>Bedankt voor je aanvraag</h2><p>ACR neemt contact op om de opdracht en planning te bespreken. Een aanvraag is nog geen bevestigde rit. Is het dringend of krijg je geen reactie? Bel 0471 37 90 64.</p></div>
<?php else: ?>
<?php if($errors): ?><div class="notice" role="alert"><h2>Controleer je aanvraag</h2><ul><?php foreach(array_unique($errors) as $error): ?><li><?=h($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="" accept-charset="UTF-8">
<input type="hidden" name="token" value="<?=h($_SESSION['quote_token'])?>">
<div class="hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
<div class="formgrid">
<div><label for="naam">Naam *</label><input id="naam" name="naam" autocomplete="name" maxlength="150" required value="<?=h($values['naam'])?>"></div>
<div><label for="telefoon">Telefoon *</label><input id="telefoon" name="telefoon" type="tel" autocomplete="tel" maxlength="30" required value="<?=h($values['telefoon'])?>"></div>
</div>
<label for="email">E-mailadres *</label><input id="email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?=h($values['email'])?>">
<label for="dienst">Welke hulp heb je nodig? *</label><select id="dienst" name="dienst" required><option value="">Kies een dienst</option><?php foreach(['Depannage / takeldienst','Voertuigtransport','Lostrekken','Motortransport','Camper / caravan','Veilingtransport','Andere aanvraag'] as $service): ?><option value="<?=h($service)?>" <?=$values['dienst']===$service?'selected':''?>><?=h($service)?></option><?php endforeach; ?></select>
<label for="voertuig">Voertuig of lading</label><input id="voertuig" name="voertuig" maxlength="250" value="<?=h($values['voertuig'])?>" placeholder="Merk, model, afmetingen of gewicht indien relevant">
<div class="formgrid"><div><label for="ophaalplaats">Ophaalplaats *</label><input id="ophaalplaats" name="ophaalplaats" maxlength="400" required value="<?=h($values['ophaalplaats'])?>"></div><div><label for="bestemming">Bestemming</label><input id="bestemming" name="bestemming" maxlength="400" value="<?=h($values['bestemming'])?>"></div></div>
<label for="datum">Gewenste datum of periode</label><input id="datum" name="datum" maxlength="150" value="<?=h($values['datum'])?>">
<label for="bericht">Beschrijf je aanvraag *</label><textarea id="bericht" name="bericht" maxlength="4000" required><?=h($values['bericht'])?></textarea>
<p class="field-note">Kan het voertuig rollen en sturen? Zijn er sleutels? Vermeld beperkte toegang of schade. Foto’s kan je via WhatsApp sturen.</p>
<label class="check"><input type="checkbox" name="privacy" value="1" required <?=($_POST['privacy']??'')==='1'?'checked':''?>><span>ACR mag mijn gegevens gebruiken om deze aanvraag te beantwoorden. Ik heb de <a href="/privacy/">privacyinformatie</a> gelezen.</span></label>
<p><button class="button" type="submit">Verstuur mijn offerteaanvraag</button></p><p class="field-note">Velden met * zijn verplicht. Je aanvraag is vrijblijvend; de uitvoering wordt afzonderlijk afgesproken.</p>
</form>
<?php endif; ?>
<?php $quote_html=(string)ob_get_clean(); ?>
<!doctype html>
<html lang="nl-BE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offerte voor takeldienst of voertuigtransport | ACR</title><meta name="description" content="Vraag een offerte bij Takeldienst / Depannage ACR. Geef voertuig, ophaalplaats en bestemming door. Dringende depannage? Bel 0471 37 90 64."><meta name="robots" content="index,follow,max-image-preview:large"><link rel="canonical" href="https://depannageoostvlaanderen.be/offerte/"><link rel="stylesheet" href="/assets/site.css"><meta property="og:type" content="website"><meta property="og:locale" content="nl_BE"><meta property="og:title" content="Offerte voor takeldienst of voertuigtransport"><meta property="og:description" content="Vraag een offerte bij Takeldienst / Depannage ACR. Geef voertuig, ophaalplaats en bestemming door. Dringende depannage? Bel 0471 37 90 64."><meta property="og:url" content="https://depannageoostvlaanderen.be/offerte/"><meta property="og:image" content="https://depannageoostvlaanderen.be/assets/acr-netwerk/acr-voertuigtransport.jpg"><script type="application/ld+json">{"@context": "https://schema.org", "@graph": [{"@type": "AutomotiveBusiness", "@id": "https://depannageoostvlaanderen.be/#bedrijf", "name": "Takeldienst / Depannage ACR", "url": "https://depannageoostvlaanderen.be/", "telephone": "+32471379064", "email": "depannageacr@hotmail.com", "areaServed": {"@type": "AdministrativeArea", "name": "Oost-Vlaanderen"}, "sameAs": ["https://www.takeldienstacr.be/", "https://www.takeldienstindebuurt.be/"]}, {"@type": "WebPage", "@id": "https://depannageoostvlaanderen.be/offerte/#pagina", "url": "https://depannageoostvlaanderen.be/offerte/", "name": "Offerte voor takeldienst of voertuigtransport", "description": "Vraag een offerte bij Takeldienst / Depannage ACR. Geef voertuig, ophaalplaats en bestemming door. Dringende depannage? Bel 0471 37 90 64.", "inLanguage": "nl-BE", "about": {"@id": "https://depannageoostvlaanderen.be/#bedrijf"}}, {"@type": "BreadcrumbList", "itemListElement": [{"@type": "ListItem", "position": 1, "name": "Home", "item": "https://depannageoostvlaanderen.be/"}, {"@type": "ListItem", "position": 2, "name": "Offerte voor takeldienst of voertuigtransport", "item": "https://depannageoostvlaanderen.be/offerte/"}]}]}</script></head><body><a class="skip" href="#inhoud">Naar de inhoud</a><div class="top"><div class="shell"><span>DEPANNAGE · TAKELDIENST · VOERTUIGTRANSPORT</span><span>Oost-Vlaanderen · 0471 37 90 64</span></div></div><header class="header"><div class="shell"><a class="brand" href="/" aria-label="ACR naar de homepage"><b>ACR</b><span>Takeldienst<br>Depannage</span></a><nav class="nav" aria-label="Hoofdnavigatie"><a href="/diensten/">Diensten</a><a href="/werkgebied/">Werkgebied</a><a href="/advies/">Vragen &amp; advies</a><a href="/over-ons/">Over ACR</a><a class="button" href="/offerte/">Offerte</a></nav></div></header><section class="hero"><div class="shell"><div class="hero-text"><div><span class="eyebrow">Takeldienst / Depannage ACR</span><h1>Offerte voor takeldienst of voertuigtransport</h1><p class="intro">Vraag een offerte bij Takeldienst / Depannage ACR. Geef voertuig, ophaalplaats en bestemming door. Dringende depannage? Bel 0471 37 90 64.</p><div class="actions"><a class="button" href="tel:+32471379064">Bel 0471 37 90 64</a><a class="button secondary" href="/offerte/">Vraag een offerte</a></div></div></div></div></section><main id="inhoud"><div class="shell layout"><article class="copy"><p class="answer">Dringende hulp? Bel 0471 37 90 64. Gebruik het formulier voor een offerte of een geplande aanvraag.</p><?php echo $quote_html; ?><figure style="margin:28px 0"><img src="/assets/acr-netwerk/acr-voertuigtransport.jpg" width="1920" height="1440" alt="Voertuig op het laadplatform van de gele ACR-takelwagen" loading="lazy"><figcaption>Voertuig op het laadplatform van de gele ACR-takelwagen</figcaption></figure></article><aside class="panel"><span class="eyebrow" style="color:#765b00">Rechtstreeks met ACR</span><h2>Welke hulp heb je nodig?</h2><p>Geef je locatie, voertuig en gewenste bestemming door. Meld het als de wagen niet rolt of stuurt.</p><ul><li>Takelen na pech of een ongeval</li><li>Voertuigtransport naar een garage</li><li>Lostrekken en moeilijk bereikbare voertuigen</li></ul><div class="actions"><a class="button" href="tel:+32471379064">Bel 0471 37 90 64</a><a class="button secondary" href="/offerte/">Vraag een offerte</a></div><p><a href="https://wa.me/32471379064">Locatie of foto’s via WhatsApp</a></p><p class="field-note">De aanpak, prijs en aankomsttijd worden afgestemd op jouw situatie.</p></aside></div></main><footer class="footer"><div class="shell footer-grid"><div><h2>Takeldienst / Depannage ACR</h2><p>Depannage en voertuigtransport in Oost-Vlaanderen. Voor dringende hulp bel je rechtstreeks; voor gepland vervoer kan je een offerte aanvragen.</p><a href="tel:+32471379064">0471 37 90 64</a><br><a href="mailto:depannageacr@hotmail.com">depannageacr@hotmail.com</a></div><div><strong>Op deze site</strong><p><a href="/diensten/">Diensten</a><br><a href="/werkgebied/">Werkgebied</a><br><a href="/advies/">Vragen en advies</a><br><a href="/contact/">Contact</a><br><a href="/privacy/">Privacy</a></p></div><div><strong>Andere ACR-diensten</strong><p><a href="https://www.autorepatriering.be/">Internationale autorepatriëring</a><br><a href="https://www.motoexpress.be/">Motortransport via MotoExpress</a><br><a href="https://www.veilingtransport.be/">Transport van veilingaankopen</a></p><p>© ACR · Depannage Oost-Vlaanderen</p></div></div></footer><nav class="sticky" aria-label="Rechtstreeks contact"><a class="button" href="tel:+32471379064">Bel 0471 37 90 64</a><a class="button secondary" href="/offerte/">Offerte aanvragen</a><a class="button secondary whatsapp" href="https://wa.me/32471379064">WhatsApp</a></nav></body></html>