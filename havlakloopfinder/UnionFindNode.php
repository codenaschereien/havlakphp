<?php

/**
 * class UnionFindNode
 *
 * The algorithm uses the Union/Find algorithm to collapse
 * complete loops into a single node. These nodes and the
 * corresponding functionality are implemented with this class
 */
class UnionFindNode {

  private $parent;
  private $bb;
  private $loop = null;
  private $dfsNumber;

  // Initialize this node.
  //
  public function initNode(BasicBlock $bb, $dfsNumber) {
    $this->parent = $this;
    $this->bb = $bb;
    $this->dfsNumber = $dfsNumber;
  }

  // Union/Find Algorithm - The find routine.
  //
  // Implemented with Path Compression (inner loops are only
  // visited and collapsed once, however, deep nests would still
  // result in significant traversals).
  //
  public function findSet() {

    $nodeList = array();

    $node = $this;
    while ($node != $node->getParent()) {
      if ($node->getParent() != $node->getParent()->getParent()) {
        $nodeList[] = $node;
      }
        $node = $node->getParent();
      }

    // Path Compression, all nodes' parents point to the 1st level parent.
    foreach ($nodeList as $iter)
      $iter->setParent($node->getParent());

    return $node;
  }

  // Union/Find Algorithm - The union routine.
  //
  // Trivial. Assigning parent pointer is enough,
  // we rely on path compression.
  //
  public function union(UnionFindNode $basicBlock) {
    $this->setParent($basicBlock);
  }

  // Getters/Setters
    //
  public function getParent() {
    return $this->parent;
  }

  public function getBb() {
    return $this->bb;
  }

  public function getLoop() {
    return $this->loop;
  }

  public function getDfsNumber() {
    return $this->dfsNumber;
  }

  public function setParent(UnionFindNode $parent) {
    $this->parent = $parent;
  }

  public function setLoop(SimpleLoop $loop) {
    $this->loop = $loop;
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }
}