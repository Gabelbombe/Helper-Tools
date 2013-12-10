<?php
header('Content-type: text/plain');


/**
 * Simple link generator script that will make links in 
 * differejnt protocols at different depths in various 
 * formats
 *
 * CPR : Jd Daniel :: Ehime-ken
 * MOD : 2013-12-10 @ 13:11:48
 * INP : Nada
 * 
 */


$path ='/var/www/tmp';

###=======================================================================###
###=======================================================================###

$rand = function () { return rand(1, 5); };
$data = function () 
{ 
	$datas = '';

	$lines = rand(1, 20); 
	$proto = ['http', 'https'];

	for ($i = 0; $i < $lines ; $i++)
	{
		$datas .= (0 === (rand(0, 5) % 2)) // make links =P
		? "<a href='{$proto[0]}://example.com/'>Test link</a>\n" 
		: "<a href='{$proto[1]}://example.com/'>Test link</a>\n";
	}

	return $datas;
};

$exts = array_flip(explode(',', 'dtd,xml,html,htm,xhtml,xht,mht,mhtml,asp,aspx,adp,bml,cfm,cgi,ihtml,jsp,las,lasso,lassoapp,pl,php,php3,php4,phtml,rna,r,rnx,shtml,stm'));

if (! is_dir($path)) mkdir($path, 0777);

foreach (array_rand($exts, rand(10, count($exts))) AS $ext)
{
	$last  = '';
	for ($i = 1; $i < ($rand() + 1) ; $i++) $last .= uniqid().'/';

	// short circuit it cuz I'm lazy....
	mkdir("{$path}/{$last}", 0777, 1) & file_put_contents("{$path}/{$last}test.$ext", $data());
}

echo "Generated folders/files...";