<?php
namespace Lychee\Component\Process;

class PidFile {
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var resource
     */
    protected $file = null;

    /**
     * Prepares new lock on the file $filename
     *
     * @param string $filename The name of the lock file
     */
    public function __construct($filename) {
        $this->filename = $filename;
    }
    /**
     * Acquire a lock on the lock file.
     *
     * @param boolean $block
     * @return boolean
     */
    public function acquireLock($block = false) {
        $dir = dirname($this->filename);
        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->file = fopen($this->filename, 'a+');
        $flag = $block ? LOCK_EX : LOCK_EX | LOCK_NB;
        if (flock($this->file, $flag)) {
            return true;
        } else {
            fclose($this->file);
            $this->file = null;
            return false;
        }
    }

    /**
     * Release the lock on the lock file
     *
     * @return boolean
     */
    public function releaseLock() {
        if (! is_resource($this->file)) {
            return false;
        }
        flock($this->file, LOCK_UN);
        fclose($this->file);
        @unlink($this->filename);
        $this->file = null;
        return true;
    }

    /**
     * Write the given PID to the lock file. The file must be locked before!
     *
     * @param string $pid The PID to write to the file
     *
     * @return int
     * @throws \RuntimeException
     */
    public function setPid($pid) {
        if (null === $this->file) {
            throw new \RuntimeException('The pidfile is not locked');
        }
        ftruncate($this->file, 0);
        return fwrite($this->file, $pid);
    }

    /**
     * Read the PID.
     *
     * @return string|null
     */
    public function getPid() {
        $pid = @file_get_contents($this->filename);
        if ($pid === false) {
            return null;
        } else {
            return $pid;
        }
    }

    /**
     * Check if the PID written in the lock file corresponds to a running process.
     * The file must be locked before!
     *
     * @return boolean
     * @throws \RuntimeException
     */
    public function isProcessRunning() {
        if (null === $this->file) {
            throw new \RuntimeException('The pidfile is not locked');
        }
        $pid = $this->getPid();
        return ($pid !== '') && file_exists("/proc/$pid");
    }

    /**
     * Kill the currently running process
     *
     * @return boolean
     */
    public function killProcess() {
        $pid = $this->getPid();
        return posix_kill($pid, SIGKILL);
    }
} 