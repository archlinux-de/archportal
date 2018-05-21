<?php

namespace App\Entity\Packages;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FilesRepository")
 */
class Files implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $files;

    /**
     * @var Package
     *
     * @ORM\OneToOne(targetEntity="App\Entity\Packages\Package", mappedBy="files", fetch="LAZY")
     */
    private $package;

    /**
     * @param string $files
     */
    private function __construct(string $files)
    {
        $this->files = $files;
    }

    /**
     * @param string[] $filesArray
     * @return Files
     */
    public static function createFromArray(array $filesArray): self
    {
        sort($filesArray);
        return new self(implode("\n", $filesArray));
    }

    /**
     * @return Package
     */
    public function getPackage(): Package
    {
        return $this->package;
    }

    /**
     * @param Package $package
     * @return Files
     */
    public function setPackage(Package $package): Files
    {
        $this->package = $package;
        return $this;
    }

    /**
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->getFiles());
    }

    /**
     * @return string[]
     */
    private function getFiles(): array
    {
        if (empty($this->files)) {
            return [];
        } else {
            return explode("\n", $this->files);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->getFiles();
    }
}