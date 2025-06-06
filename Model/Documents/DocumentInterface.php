<?php

/**
 * Interface DocumentInterface
 * @package Kwz\Certification\Model\Documents
 */
namespace Kwz\Certification\Model\Documents;



interface DocumentInterface
{

    public function loadDocument(string $id, bool $isIncrementId);

    public function get();

    public function send();

    public function getPdfHash();

    public function canBeSent();

}
