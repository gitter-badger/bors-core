<?
    function lcml_pure_mail($txt)
    {
		$mail_chars = 'a-zA-Z0-9\_\-\+\.';
        return preg_replace("!(^|[\s\]])([$mail_chars]+@[$mail_chars]+)([\s\[;\.:]|$)!ime", "'$1'.mask_email('$2', ".(config('lcml_email_nomask') ? 'false' : 'true').").'$3'", $txt);
    }

	function mask_email($email, $img_mask = true, $text = NULL)
	{
		list($user, $domain) = explode('@', $email);
		$rev = "";
		for($i=strlen($email)-1; $i>=0; $i--)
			$rev .= $email[$i];

		if(!$text)
			$text = $user.($img_mask ? "<span style=\"color: red;\"><img src=\"/_bors/i/rt.gif\" width=\"16\" height=\"16\" align=\"absmiddle\"/></span>" : "<span>&#64;</span>")
			.$domain;

		return "<script type=\"text/javascript\">document.write('<a href='+'\"'+'ma'+'i'+'lto'+':' +'".addslashes($rev)."'.split('').reverse().join('') +'\">')</script>{$text}<script type=\"text/javascript\">document.write('</'+'a>')</script>";
	}
