<?php

namespace App\ArchLinux;

class TemporaryFile extends \SplFileObject
{
    /** @var string */
    private $fileName;

    /** @var int */
    private $mTime;

    /**
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $fileName = tempnam(sys_get_temp_dir(), $prefix);
        if (!$fileName) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Could not create temporyry file "%s".', $prefix));
            // @codeCoverageIgnoreEnd
        }
        $this->fileName = $fileName;
        parent::__construct($this->fileName);
        $this->mTime = parent::getMTime();
    }

    public function __destruct()
    {
        if (is_writable($this->fileName)) {
            unlink($this->fileName);
        }
    }

    /**
     * @return int
     */
    public function getMTime(): int
    {
        return $this->mTime;
    }

    /**
     * @param int $mtime
     */
    public function setMTime(int $mtime): void
    {
        $filePath = $this->getRealPath();
        if (!$filePath) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Could not find file "%s".', $filePath));
            // @codeCoverageIgnoreEnd
        }
        touch($filePath, $mtime);
        $this->mTime = $mtime;
    }
}
