<?php

//======================================================
// Scaffold Code
//======================================================

/**
 * The Havlak loop finding algorithm.
 *
 */


/**
 * class SimpleLoop
 *
 * Basic representation of loops, a loop has an entry point,
 * one or more exit edges, a set of basic blocks, and potentially
 * an outer loop - a "parent" loop.
 *
 * Furthermore, it can have any set of properties, e.g.,
 * it can be an irreducible loop, have control flow, be
 * a candidate for transformations, and what not.
 */
class SimpleLoop {

  private $basicBlocks = array();
  private $children = array();

  private $parent = null;
  private $header = null;

  private $isRoot = false;
  private $isReducible = true;
  private $counter;
  private $nestingLevel = 0;
  private $depthLevel = 0;


  public function addNode(BasicBlock $bb) {
    $this->basicBlocks[] = $bb;
  }

  public function addChildLoop(SimpleLoop $loop) {
    $this->children[] = $loop;
  }

  public function dump($indent) {
    for ($i=0; $i < $indent; ++$i)
      echo '  ';

    printf(
      "loop-%d nest: %d depth %d %s",
      $this->counter, $this->nestingLevel, $this->depthLevel,
      $this->isReducible ? '' : '(Irreducible) '
    );
    if (count($this->getChildren()) > 0) {
      echo 'Children: ';
      foreach ($this->getChildren() as $loop) {
        printf("loop-%d ", $loop->getCounter());
      }
    }
    if (count($this->basicBlocks) > 0) {
      echo '(';
      foreach ($this->basicBlocks as $bb) {
        printf("BB#%d%s", $bb->getName(), $this->header == $bb ? "* " : " ");
      }
      echo "\b)";
    }
    echo "\n";
  }

  // Getters/Setters
  public function getChildren() {
    return $this->children;
  }

  public function getParent() {
    return $this->parent;
  }
  public function getNestingLevel(){
    return $this->nestingLevel;
  }
  public function getDepthLevel() {
    return $this->depthLevel;
  }
  public function getCounter() {
    return $this->counter;
  }
  public function isRoot() {   // Note: fct and var are same!
    return $this->isRoot;
  }

  public function setParent(SimpleLoop $parent) {
    $this->parent = $parent;
    $this->parent->addChildLoop($this);
  }

  public function setHeader(BasicBlock $bb) {
    $this->basicBlocks[] = ($bb);
    $this->header = $bb;
  }

  public function setIsRoot() {
    $this->isRoot = true;
  }

  public function setCounter($value) {
    $this->counter = $value;
  }
  public function setNestingLevel($level) {
    $this->nestingLevel = $level;
    if ($level == 0) {
      $this->setIsRoot();
    }
  }

  public function setDepthLevel($level) {
    $this->depthLevel = $level;
  }

  public function setIsReducible($isReducible) {
    $this->isReducible = $isReducible;
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }
}
