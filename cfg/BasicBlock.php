<?php

class BasicBlock {

  private $name;
  private $inEdges = array();
  private $outEdges = array();

  // BasicBlock's static members
  private static $numBasicBlocks = 0;

  public static function getNumBasicBlocks() {
    return self::$numBasicBlocks;
  }

  public function __construct($name) {
    if (!is_int($name)) {
      throw new Exception('Invalid value given for $name: ' . $name);
    }
    $this->name = $name;

    ++self::$numBasicBlocks;
  }

  public function dump() {
    printf("BB#%03d: ", $this->getName());
    if (count($this->inEdges) > 0) {
      echo 'in : ';
      foreach ($this->inEdges as $bb) {
        printf("BB#%03d ", $bb->getName());
      }
    }
    if (count($this->outEdges) > 0) {
      echo 'out: ';
      foreach ($this->outEdges as $bb) {
        printf("BB#%03d ", $bb->getName());
      }
    }
    echo "\n";
  }

  public function getName() {
    return $this->name;
  }

  public function getInEdges() {
    return $this->inEdges;
  }

  public function getOutEdges() {
    return $this->outEdges;
  }

  public function getNumPred() {
    return count($this->inEdges);
  }

  public function getNumSucc() {
    return count($this->outEdges);
  }

  public function addOutEdge(BasicBlock $to) {
    $this->outEdges[] = $to;
  }

  public function addInEdge(BasicBlock $from) {
    $this->inEdges[] = $from;
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }

}