<?php

	/*
	 * Copyright (c) 2011, Geoffrey Cox
	 *
	 * Permission is hereby granted, free of charge, to any person
	 * obtaining a copy of this software and associated documentation
	 * files (the "Software"), to deal in the Software without
	 * restriction, including without limitation the rights to use,
	 * copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the
	 * Software is furnished to do so, subject to the following
	 * conditions:
	 *
	 * The above copyright notice and this permission notice shall be
	 * included in all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	 * OTHER DEALINGS IN THE SOFTWARE.
	 *
	 * Developed by: Geoffrey Cox, http://brainbooksoftware.com
	 *
	 */

	// This code uses Westhoff's Quick Hull implementation
	require_once(dirname(__FILE__) . "/convex_hull.php");

	class BbsPoint
	{
		public $x;
		public $y;

		public function __construct($x=0, $y=0)
		{
			$this->x = $x;
			$this->y = $y;
		}
		
		public function reducePrecision()
		{
			$this->x = round($this->x, 5);
			$this->y = round($this->y, 5);
		}

		public function __toString()
		{
			return "(" . $this->x . "," . $this->y . ")";
		}
	}

	class BbsVector extends BbsPoint {}

	class BbsRotatingCalipers
	{
		/**
		 * Provides a kind of wrapping so that array indexes are easier to use
		 */
		public static function getItem(&$a, $i)
		{
			$sz = sizeof($a);
			if ($i >= $sz)
				$i = $i % $sz;
			return $a[$i];
		}

		/**
		 * Returns the angle in radians between vector v1 and vector v2
		 */
		public static function angle($v1, $v2)
		{
			// This fn breaks if there are too many decimal places
			$v1->reducePrecision();
			$v2->reducePrecision();
			
			$n = $v1->x*$v2->x + $v1->y*$v2->y;
			$d = sqrt($v1->x*$v1->x + $v1->y*$v1->y)*sqrt($v2->x*$v2->x + $v2->y*$v2->y);
			return acos($n/$d);
		}

		/**
		 * Rotates the vector v r radians
		 */
		public static function rotate($v, $r)
		{
			$v2 = new BbsVector();
			$v2->x = $v->x*cos($r) - $v->y*sin($r);
			$v2->y = $v->x*sin($r) + $v->y*cos($r);
			return $v2;
		}

		/**
		 * Shortest distance from point p to the line formed by extending the vector v through point t
		 */
		public static function distance($p, $t, $v)
		{
			if ($v->x == 0)
				return abs($p->x - $t->x);
			$a = $v->y/$v->x;
			$c = $t->y - $a*$t->x;
			return abs($p->y - $c - $a*$p->x)/sqrt($a*$a + 1);
		}

		/**
		 * Finds the intersection of the lines formed by vector v1 passing through
		 * point p1 and vector v2 passing through point p2
		 */
		public static function intersection($p1, $v1, $p2, $v2)
		{
			// This fn breaks if there are too many decimal places
			$v1->reducePrecision();
			$v2->reducePrecision();

			if ($v1->x == 0 && $v2->x == 0)
				return false;

			if ($v1->x != 0) {
				$m1 = $v1->y/$v1->x;
				$b1 = $p1->y - $m1*$p1->x;
			}

			if ($v2->x != 0) {
				$m2 = $v2->y/$v2->x;
				$b2 = $p2->y - $m2*$p2->x;
			}

			if ($v1->x == 0) {
				return new BbsPoint($p1->x, $m2*$p1->x + $b2);
			} else if ($v2->x == 0) {
				return new BbsPoint($p2->x, $m1*$p2->x + $b1);
			}

			if ($m1 == $m2)
				return false;

			$p = new BbsPoint();
			$p->x = ($b2 - $b1)/($m1 - $m2);
			$p->y = $m1*$p->x + $b1;
			return $p;
		}

		/**
		 * A wrapper that gets the convex hull in our desired format using Westhoff's function
		 */
		public static function convexHullPoints($points)
		{
			$whPoints = array();
			foreach ($points as $p) {
				array_push($whPoints, array($p->x, $p->y));
			}
			$hull = new ConvexHull($whPoints);
			$hullPoints = $hull->getHullPoints();
			$newPoints = array();
			for ($i = sizeof($hullPoints) - 1; $i >= 0; $i--) {
				array_push($newPoints, new BbsPoint($hullPoints[$i][0], $hullPoints[$i][1]));
			}
			return $newPoints;
		}

		/**
		 * Calculates the area of the minimum bounding rectangle of a polygon. Besides
		 * the convex hull calculation, this function completes in O(n) time 
		 * where n is the number of points in the polygon.
		 * This function uses the "Rotating Calipers" algorithm. For a detailed
		 * explanation of this algorithm, see: Toussaint, Godfried T.
		 * (1983). Solving geometric problems with the rotating calipers. Proc.
		 * MELECON '83, Athens.
		 * Special thanks to the wikipedia page that contained pseudocode for this
		 * algorithm and Bart Kiers for his JAVA implementation.
		 * Precondition: The points must be in a counterclockwise order
		 * e.g. minBoundingArea(
		 *         array(new Point(1, 1),
		 *               new Point(2, 0),
		 *               new Point(3, 2)));
		 *
		 */
		public static function getMinBoundingArea($points)
		{
			$points = self::convexHullPoints($points);

			$pA = 0; // index of vertex with minimum y-coordinate
			$pB = 0; // index of vertex with maximum y-coordinate
			$pC = 0; // index of vertex with minimum x-coordinate
			$pD = 0; // index of vertex with maximum x-coordinate	
			for ($i = 1; $i < sizeof($points); $i++) {
				if ($points[$i]->y < $points[$pA]->y)
					$pA = $i;
				if ($points[$i]->y > $points[$pB]->y)
					$pB = $i;
				if ($points[$i]->x < $points[$pC]->x)
					$pC = $i;
				if ($points[$i]->x > $points[$pD]->x)
					$pD = $i;
			}

			$rotatedAngle = 0;
			$minArea = null;
			$minWidth = null;
			$minHeight = null;
			$minAPair = null;
			$minBPair = null;
			$minCPair = null;
			$minDPair = null;

			$caliperA = new BbsVector(1, 0); // Caliper A points along the positive x-axis
			$caliperB = new BbsVector(-1, 0); // Caliper B points along the negative x-axis
			$caliperC = new BbsVector(0, -1); // Caliper D points along the negative y-axis
			$caliperD = new BbsVector(0, 1); // Caliper C points along the positive y-axis

			while ($rotatedAngle < M_PI) {

				// Determine the angle between each caliper and the next adjacent edge in the polygon
				$edgeA = new BbsVector(self::getItem($points, $pA + 1)->x - self::getItem($points, $pA)->x,
						       self::getItem($points, $pA + 1)->y - self::getItem($points, $pA)->y);
				$edgeB = new BbsVector(self::getItem($points, $pB + 1)->x - self::getItem($points, $pB)->x,
						       self::getItem($points, $pB + 1)->y - self::getItem($points, $pB)->y);
				$edgeC = new BbsVector(self::getItem($points, $pC + 1)->x - self::getItem($points, $pC)->x,
						       self::getItem($points, $pC + 1)->y - self::getItem($points, $pC)->y);
				$edgeD = new BbsVector(self::getItem($points, $pD + 1)->x - self::getItem($points, $pD)->x,
						       self::getItem($points, $pD + 1)->y - self::getItem($points, $pD)->y);

				$angleA = self::angle($edgeA, $caliperA);
				$angleB = self::angle($edgeB, $caliperB);
				$angleC = self::angle($edgeC, $caliperC);
				$angleD = self::angle($edgeD, $caliperD);
				$area = 0;

				// Rotate the calipers by the smallest of these angles
				$minAngle = min($angleA, $angleB, $angleC, $angleD);
				$caliperA = self::rotate($caliperA, $minAngle);
				$caliperB = self::rotate($caliperB, $minAngle);
				$caliperC = self::rotate($caliperC, $minAngle);
				$caliperD = self::rotate($caliperD, $minAngle);

				if ($angleA == $minAngle) {
					$width = self::distance(self::getItem($points, $pB),
							  self::getItem($points, $pA),
							  $caliperA);

					$height = self::distance(self::getItem($points, $pD),
								 self::getItem($points, $pC),
								 $caliperC);
				} else if ($angleB == $minAngle) {
					$width = self::distance(self::getItem($points, $pA),
								self::getItem($points, $pB),
								$caliperB);

					$height = self::distance(self::getItem($points, $pD),
								 self::getItem($points, $pC),
								 $caliperC);
				} else if ($angleC == $minAngle) {
					$width = self::distance(self::getItem($points, $pB),
								self::getItem($points, $pA),
								$caliperA);

					$height = self::distance(self::getItem($points, $pD),
								 self::getItem($points, $pC),
								 $caliperC);
				} else {
					$width = self::distance(self::getItem($points, $pB),
								self::getItem($points, $pA),
								$caliperA);

					$height = self::distance(self::getItem($points, $pC),
								 self::getItem($points, $pD),
								 $caliperD);
				}

				$rotatedAngle = $rotatedAngle + $minAngle;
				$area = $width*$height;

				if ($minArea === null || $area < $minArea) {
					$minArea = $area;
					$minAPair = array(self::getItem($points, $pA), $caliperA);
					$minBPair = array(self::getItem($points, $pB), $caliperB);
					$minCPair = array(self::getItem($points, $pC), $caliperC);
					$minDPair = array(self::getItem($points, $pD), $caliperD);
					$minWidth = $width;
					$minHeight = $height;
				}

				if ($angleA == $minAngle) {
					$pA++;
				} else if ($angleB == $minAngle) {
					$pB++;
				} else if ($angleC == $minAngle) {
					$pC++;
				} else {
					$pD++;
				}
				
				// Prevent an infinite loop even if our precision leads to a NaN
				if (is_nan($rotatedAngle))
					break;
			}

			$vertices = array(
				self::intersection($minAPair[0], $minAPair[1], $minDPair[0], $minDPair[1]),
				self::intersection($minDPair[0], $minDPair[1], $minBPair[0], $minBPair[1]),
				self::intersection($minBPair[0], $minBPair[1], $minCPair[0], $minCPair[1]),
				self::intersection($minCPair[0], $minCPair[1], $minAPair[0], $minAPair[1])
			);

			return array("vertices" => $vertices, "area" => $minArea,
			             "width" => $minWidth, "height" => $minHeight);
		}
	}

?>