<?php
/* fim: [exco] encoding function for lecture on demand calls. */
class ilEncodingWi2
{
		static function encode($input) {
	
		$CHARS_PER_BLOCK = 5;
		$SPLIT_CHAR = "X";
	
		$parts = preg_split("/;/", $input, -1);
	
		$idt = $parts[0];
		$lid = $parts[1];
		$code = $parts[2];
		$rip = $parts[3];
		$sid = $parts[4];
    	$uid = $parts[5];
    	$ulg = $parts[6];
    	$name = $parts[7];
    	$n = $parts[8];
    	$e = $parts[9];

    	$time = time();

    	$hash = md5($idt. $lid . $rip . $sid . $time . $uid . $ulg . $code);
		$completestring = "idt=" . $idt . "&lid=" . $lid . "&rip=" . $rip . "&sid="
		. $sid . "&ts=" . $time . "&uid=" . $uid . "&ulg=" . $ulg . "&hash=" . $hash;
		$asci = array ();
		for ($i=0; $i<strlen($completestring); $i+=$CHARS_PER_BLOCK) {
			$tmpasci="";
			for ($h=0; $h<$CHARS_PER_BLOCK; $h++) {
				if ($i+$h <strlen($completestring)) {
					$tmpstr = ord (substr ($completestring, $i+$h, 1)) - 30;
					if (strlen($tmpstr) < 2) {
						$tmpstr ="0".$tmpstr;
					}
				} else {
					break;
				}
				$tmpasci .=$tmpstr;
			}
			array_push($asci, $tmpasci."");
		}
	
		$coded = "";
		for ($k=0; $k< count ($asci); $k++) {
	
			$basepow2 = $asci[$k];
			$exppow2 = $e;
			$retvalue = 0;
	
			if (1 == bcmod($exppow2, 2)) {
				$retvalue = bcmod(bcadd($retvalue, $basepow2), $n);
			}
			do {
				$basepow2 = bcmod(bcmul($basepow2, $basepow2), $n);
				$exppow2 = bcdiv($exppow2, 2);
				if (1 == bcmod($exppow2, 2)) {
					$retvalue = bcmod(bcmul($retvalue, $basepow2), $n);
				}
			} while (1 == bccomp($exppow2, 0));
			$retvalue = bcmod($retvalue, $n);
			
			$coded .= $retvalue . $SPLIT_CHAR;
		}
		return trim($coded);
	}

}
?>
