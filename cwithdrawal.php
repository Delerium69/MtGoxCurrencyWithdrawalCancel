<?php

// Set Timezone
//date_timezone_set('Europe/London');
date_default_timezone_set('Europe/London');

//Get Database Connection Strings
include "db_connect.php";

//Get the donated total
$query = "SELECT text_val FROM `mtgox_param` WHERE param='donated'";
$result = mysql_query($query);echo mysql_error();
$params = @mysql_fetch_row($result);
$update_donated = $params[0];

$key = $_POST['mt_key'];
$secret = $_POST['mt_secret'];
$wid = $_POST['wid'];

function mtgox_query($path, $key, $secret, array $req = array()) {
	// generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
	$mt = explode(' ', microtime());
	$req['nonce'] = $mt[1].substr($mt[0], 2, 6);
	
	// generate the POST data string
	$post_data = http_build_query($req, '', '&');
	$prefix = '';
	if (substr($path, 0, 2) == '2/') {
		$prefix = substr($path, 2)."\0";
	}

	// generate the extra headers
	$headers = array(
		'Rest-Key: '.$key,
		'Rest-Sign: '.base64_encode(hash_hmac('sha512', $prefix.$post_data, base64_decode($secret), true)),
	);

	// our curl handle (initialize if required)
	static $ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://data.mtgox.com/api/'.$path);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
	// run the query
	//	if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
	// If Failed to Get page try the backup API	
	$res = curl_exec($ch);
	if ($res === false) {
		curl_setopt($ch, CURLOPT_URL, 'https://mtgox.com/api/'.$path);	
		$res = curl_exec($ch);
	}	
	// Else throw exception if failed again
	if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
	//echo $res;
	return $res;	
}


// Page Template
$indexpage = "<html>
<head>
<title>MtGox Currency Withdrawal Cancellations</title>
<link href=\"colour2.css\" rel=\"stylesheet\" type=\"text/css\">
</head>
<body bgcolor=\"#000000\" topmargin=\"1\"><BR>
<div align=\"center\"><img src=\"images/cancel.png\"></div></TD>
<BR>";

echo $indexpage;

echo "<div align=\"center\" style=\"color:#FF0000; font-size: 48pt\"><b>READ FIRST!</b></div><BR>";

echo "
<BR><TABLE class=\"mainpage\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\" ALIGN=\"center\" width=\"0%\">
<TD class=\"$rowcolour defsize\" valign=\"middle\">
<font size=\"2\" color=\"red\"><B>You use this page at YOUR OWN RISK. I do not accept any responsibility for any information that has been unlawfully obtained from this page or webserver. <BR>This page has not been created nor supported by MtGox.</B></font><BR>
<BR><BR>
<u>Instructions</u><BR><BR>
1. Create an API key by going into your MtGox account and clicking on security centre or go here: https://www.mtgox.com/security<BR>
2. Click on 'Advanced API Creation' at the bottom and enter your key name. Somethink like 'API Withdrawal' will do. <BR>
3. On rights, select ONLY GetInfo AND Withdraw checkboxes and then click create key. <font size=\"1\" color=\"red\">Withdraw API is a very dangerous API to enable. Ensure you delete your key immediately.</font><BR> 
4. Make a note of this API's key and secret immediately as this is required for this webpage.<BR>
5. Now enter your key and secret in the boxes below and click submit.<BR>
6. The website will now return a list of your withdrawals with a status of 'Confirmed' only. Select the withdrawal you want to cancel and click cancel.<BR>
7. The website will come back with a success or fail and, if successful, your currency will be back in your MtGox account.<BR>
<BR><BR>
Once you have used this webpage I <font size=\"4\" color=\"red\">STRONGLY</font> advise that you go into your security centre, click on 'Current API Keys' and click the red cross in the <BR>
corner to delete it. This ensures that should this website be hacked, the hackers will gain no information that they could use to hack your account.<BR><BR>
Source code available here: https://github.com/Delerium69/MtGoxCurrencyWithdrawalCancel
</TD>
</TABLE>
<BR><BR>
";

if(!isset($_POST['mt_key'])) {

echo "<form name=\"mtgoxwd\" method=\"post\" action=\"cwithdrawal.php\"></td></tr>";

echo "
  <BR><TABLE class=\"mainpage\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\" ALIGN=\"center\" width=\"0%\">
  <TR>
    <TD class=\"$rowcolour defsize\" valign=\"middle\">KEY: <div align=\"center\"><input type=\"text\" name=\"mt_key\" value=\"\" maxlength=\"200\" size=\"100\"></div></TD>  
  </TR>
  <TR>
    <TD class=\"$rowcolour defsize\" valign=\"middle\">SECRET: <div align=\"center\"><input type=\"text\" name=\"mt_secret\" value=\"\" maxlength=\"200\" size=\"100\"></div></TD>  
  </TR>
  <TR>
    <TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\"><input type=\"submit\" name=\"Submit\" value=\"Search\"></div></TD>  
  </TR>
  </TABLE><BR><BR></form>
";
}

$output = mtgox_query('2/money/bank/list_pending',$key,$secret);
	
//echo $output;
$outputj = json_decode($output, true);

if($outputj['result']=="success") {


echo "
  <TABLE class=\"mainpage\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\" ALIGN=\"center\" width=\"50%\">
  <TR>
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Tx ID</div></TH>  
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Requested Date</div></TH>
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Amount</div></TH>
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Currency</div></TH>
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Status</div></TH>
  <TH class=\"tableth\" valign=\"top\"><div align=\"center\">Cancel?</div></TH>
  </TR>
  ";
	foreach($outputj['data'] as $p) {

		$id = $p['Id'];
		$amount = $p['Amount']['value'];
		$curr = $p['Amount']['currency'];
		$status = $p['Status'];
		$requested = $p['Requested'];		
		
		echo"<form name=\"mtgoxwd\" method=\"post\" action=\"ccancel.php\">
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\">$id</div></TD>
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\">$requested</div></TD>
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\">$amount</div></TD>
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\">$curr</div></TD>
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\">$status</div></TD>
		<TD class=\"$rowcolour defsize\" valign=\"middle\"><div align=\"center\"><input type=\"submit\" name=\"Submit\" value=\"Cancel\"></TD></div></TD>
		<input type=\"hidden\" name=\"mt_key\" value=\"$key\" maxlength=\"200\" size=\"100\">
		<input type=\"hidden\" name=\"mt_secret\" value=\"$secret\" maxlength=\"200\" size=\"100\">
		<input type=\"hidden\" name=\"mt_id\" value=\"$id\" maxlength=\"200\" size=\"100\">
		</form>
		";
		//echo "<BR>$id,$amount,$curr,$status,$requested<BR>";
		
	}

}

echo "<div align=\"center\">I do not require any personal donations, I'm here to help customers like me and to be a small help to the success of Bitcoin.<BR><BR>
If you do insist you want to donate, I have setup a generated a vanity bitcoin address which I will send all proceeds direct to Cancer Research UK. <BR>
My father, sadly, lost his battle against Lung Cancer a few years ago so this is a very personal cause to me and one we can all help to beat.<BR>Many Thanks<BR>Delerium<BR><BR>
Cancer Research UK Donations: 1CancerUkky9X6YsGdS67EoUR84vHGhm8f<BR><BR>
You can read about the good progress they have already made <a href=\"http://www.cancerresearchuk.org/home/\">here. </a><BR><BR>
Thank you so much for those who have donated to this cause. Total so far is <b>$update_donated BTC</b><BR>Donation's so far references: D0534601, D0555545, D0557872, D0557922, D0558161 & D0558528 (Total Donated 265 GBP).
<BR><BR>
</DIV>";

?>