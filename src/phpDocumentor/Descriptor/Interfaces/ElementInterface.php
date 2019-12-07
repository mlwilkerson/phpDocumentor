<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Descriptor\Interfaces;

use phpDocumentor\Descriptor\Collection;
use phpDocumentor\Descriptor\FileDescriptor;
use phpDocumentor\Reflection\Fqsen;

/**
 * Represents the public interface to which all descriptors should be held.
 */
interface ElementInterface
{
    /**
     * Sets the Fully Qualified Structural Element Name (FQSEN) for this element.
     */
    public function setFullyQualifiedStructuralElementName(Fqsen $name) : void;

    /**
     * Returns the Fully Qualified Structural Element Name (FQSEN) for this element.
     */
    public function getFullyQualifiedStructuralElementName() : ?Fqsen;

    /**
     * Sets the local name for this element.
     */
    public function setName(string $name) : void;

    /**
     * Returns the local name for this element.
     */
    public function getName() : string;

    /**
     * Sets a summary describing this element.
     */
    public function setSummary(string $summary) : void;

    /**
     * Returns the summary describing this element.
     */
    public function getSummary() : string;

    /**
     * Sets a longer description for this element.
     */
    public function setDescription(string $description) : void;

    /**
     * Returns a longer description for this element.
     */
    public function getDescription() : string;

    /**
     * Sets the file and location for this element.
     */
    public function setLocation(FileDescriptor $file, int $line = 0) : void;

    /**
     * Returns the file location for this element relative to the project root.
     */
    public function getPath() : string;

    /**
     * Returns the line number where this element may be found.
     *
     * @see getPath() to find out in which file this element is found.
     */
    public function getLine() : int;

    /**
     * Returns all tags associated with this element.
     */
    public function getTags() : Collection;
}