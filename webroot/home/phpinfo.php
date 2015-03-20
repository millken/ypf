<?php

$total = 0;
for ($i1=1;$i1<=4;$i1++) {
for ($i11=1;$i11<=4;$i11++) {	
	for ($i2=1;$i2<=4;$i2++) {
	for ($i22=1;$i22<=4;$i22++) {
		for ($i3=1;$i3<=4;$i3++) {
		for ($i33=1;$i33<=4;$i33++) {
			echo $i1 . $i11 . $i2 . $i22 . $i3 . $i33 . "<br />";
			$total ++ ;
		}
	}
}
}
}
}
echo $total;