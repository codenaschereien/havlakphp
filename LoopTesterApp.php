<?php

//======================================================
// Test Code
//======================================================

/**
* Test Program for the Havlak loop finder.
*
* This program constructs a fairly large control flow
* graph and performs loop recognition. This is the PHP
* version.
*
*/
ini_set('memory_limit', '2048M');
error_reporting(E_ALL);
ini_set('display_errors', '1');


require('cfg/CFG.php');
require('cfg/BasicBlock.php');
require('cfg/BasicBlockEdge.php');
require('lsg/LSG.php');
require('lsg/SimpleLoop.php');
require('havlakloopfinder/UnionFindNode.php');
require('havlakloopfinder/HavlakLoopFinder.php');

// Create 4 basic blocks, corresponding to and if/then/else clause
// with a CFG that looks like a diamond
function buildDiamond($start) {

  if (!is_int($start)) {
    throw new Exception('Invalid value given for parameter $start: ' . $start);
  }

  global $cfg;
  $bb0 = $start;
  new BasicBlockEdge($cfg, $bb0, $bb0 + 1);
  new BasicBlockEdge($cfg, $bb0, $bb0 + 2);
  new BasicBlockEdge($cfg, $bb0 + 1, $bb0 + 3);
  new BasicBlockEdge($cfg, $bb0 + 2, $bb0 + 3);

  return $bb0 + 3;
}

// Connect two existing nodes
function buildConnect($start, $end) {
  if (!is_int($start)) {
    throw new Exception('Invalid value given for parameter $start: ' . $start);
  }
  if (!is_int($end)) {
    throw new Exception('Invalid value given for parameter $end: ' . $end);
  }

  global $cfg;
  new BasicBlockEdge($cfg, $start, $end);
}

// Form a straight connected sequence of n basic blocks
function buildStraight($start, $n) {
  if (!is_int($start)) {
    throw new Exception('Invalid value given for parameter $start: ' . $start);
  }
  if (!is_int($n)) {
    throw new Exception('Invalid value given for parameter $end: ' . $n);
  }
  for ($i = 0; $i < $n; $i++) {
    buildConnect($start + $i, $start + $i + 1);
  }
  return $start + $n;
}

// Construct a simple loop with two diamonds in it
function buildBaseLoop($from) {
  $header = buildStraight($from, 1);
  $diamond1 = buildDiamond($header);
  $d11 = buildStraight($diamond1, 1);
  $diamond2 = buildDiamond($d11);
  $footer = buildStraight($diamond2, 1);
  buildConnect($diamond2, $d11);
  buildConnect($diamond1, $header);

  buildConnect($footer, $from);
  $footer = buildStraight($footer, 1);
  return $footer;
}

function getMem() {
  $val = memory_get_usage(true) / 1024;
  echo ' Total Memory: ' . $val . " KB\n";
}


$cfg = new CFG();
$lsg = new LSG();

echo "Welcome to LoopTesterApp, PHP edition\n";

echo "Constructing App...\n";
$root = $cfg->createNode(0);
getMem();

echo "Constructing Simple CFG...\n";
$cfg->createNode(0);
buildBaseLoop(0);
$cfg->createNode(1);
new BasicBlockEdge($cfg, 0, 2);

echo "15000 dummy loops\n";
for ($dummyloop = 0; $dummyloop < 15000; $dummyloop++) {
  $finder = new HavlakLoopFinder($cfg, $lsg);
  $finder->findLoops();
}

echo "Constructing CFG...\n";
$n = 2;

for ($parlooptrees = 0; $parlooptrees < 10; $parlooptrees++) {
  $cfg->createNode($n + 1);
  buildConnect(2, $n + 1);
  $n = $n + 1;

  for ($i = 0; $i < 100; $i++) {
    $top = $n;
    $n = buildStraight($n, 1);
    for ($j = 0; $j < 25; $j++) {
      $n = buildBaseLoop($n);
    }
    $bottom = buildStraight($n, 1);
    buildConnect($n, $top);
    $n = $bottom;
  }
  buildConnect($n, 1);
}

getMem();
echo "Performing Loop Recognition\n1 Iteration\n";
$finder = new HavlakLoopFinder($cfg, $lsg);
$finder->findLoops();
getMem();

echo "Another 50 iterations...\n";
for ($i = 0; $i < 50; $i++) {
  echo '.';
  $finder2 = new HavlakLoopFinder($cfg, new LSG());
  $finder2->findLoops();
}

echo "\n";
getMem();
echo '# of loops: ' . $lsg->getNumLoops() .
  " (including 1 artificial root node)\n";
echo '# of BBs  : ' . BasicBlock::getNumBasicBlocks() . "\n";
echo '# max time: ' . $finder->getMaxMillis() . "\n";
echo '# min time: ' . $finder->getMinMillis() . "\n";
$lsg->calculateNestingLevel();
echo "\n";

