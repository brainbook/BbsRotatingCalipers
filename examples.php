<?php

	require_once(dirname(__FILE__) . "/BbsRotatingCalipers.php");

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(0, 0), new BbsPoint(1, 0),
		new BbsPoint(1, 1), new BbsPoint(0, 1)))); // correct min area = 1

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(1, 1), new BbsPoint(2, 0), new BbsPoint(3, 2)))); // correct min area = 3

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(69, 129), new BbsPoint(116, 50),
		new BbsPoint(179, 92), new BbsPoint(151, 196)))); // correct min area = 11238.153846154

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(59, 40), new BbsPoint(171, 36),
		new BbsPoint(204, 129), new BbsPoint(124, 204),
		new BbsPoint(53, 133)))); // correct min area = 25013.42

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(0, 0), new BbsPoint(10, 0),
		new BbsPoint(10, 10), new BbsPoint(0, 10)))); // correct min area = 100

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(0, 0), new BbsPoint(5, 0),
		new BbsPoint(7, 0), new BbsPoint(10, 0),
		new BbsPoint(10, 10), new BbsPoint(0, 10)))); // correct min area = 100

	echo "<hr/>\n\n";

	print_r(BbsRotatingCalipers::getMinBoundingArea(array(
		new BbsPoint(0, 4), new BbsPoint(136, 4),
		new BbsPoint(161, 122), new BbsPoint(80, 71),
		new BbsPoint(63, 139), new BbsPoint(13, 121)))); // correct min area = 20744.12

?>