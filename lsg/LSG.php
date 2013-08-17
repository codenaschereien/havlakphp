<?php

//======================================================
// Scaffold Code
//======================================================

/**
 * Loop Structure Graph - Scaffold Code
 *
 */


/**
 * LoopStructureGraph
 *
 * Maintain loop structure for a given CFG.
 *
 * Two values are maintained for this loop graph, depth, and nesting level.
 * For example:
 *
 * loop        nesting level    depth
 *----------------------------------------
 * loop-0      2                0
 *   loop-1    1                1
 *   loop-3    1                1
 *     loop-2  0                2
 */
class LSG {

  private $loopCounter = 0;
  private $loops = array();
  private $root;

  public function __construct() {

    $this->root = new SimpleLoop();
    $this->root->setNestingLevel(0);
    $this->root->setCounter($this->loopCounter++);
    $this->addLoop($this->root);
  }

  public function createNewLoop() {
    $loop = new SimpleLoop();
    $loop->setCounter($this->loopCounter++);
    return $loop;
  }

  public function addLoop(SimpleLoop $loop) {
    $this->loops[] = $loop;
  }

  public function dump() {
    $this->dumpRec($this->root, 0);
  }

  public function dumpRec(SimpleLoop $loop, $indent) {
    // Simplified for readability purposes.
    $loop->dump($indent);

    foreach ($loop->getChildren() as $liter) {
      $this->dumpRec($liter, $indent + 1);
    }
  }

  public function calculateNestingLevel() {
    // link up all 1st level loops to artificial root node.
    foreach ($this->loops as $liter) {
      if ($liter->isRoot()) {
        continue;
      }
      if ($liter->getParent() == null) {
        $liter->setParent($this->root);
      }
    }

    // recursively traverse the tree and assign levels.
    $this->calculateNestingLevelRec($this->root, 0);
  }

  public function calculateNestingLevelRec(SimpleLoop $loop, $depth) {
    $loop->setDepthLevel($depth);
    foreach ($loop->getChildren() as $liter) {
      $this->calculateNestingLevelRec($liter, $depth + 1);

      $loop->setNestingLevel(max($loop->getNestingLevel(),
        1 + $liter->getNestingLevel()));
    }
  }

  public function getNumLoops() {
    return count($this->loops);
  }

  public function getRoot() {
    return $this->root;
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }
}
