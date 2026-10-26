--TEST--
GH-24674 (Fiber switches are not allowed inside Serializable::unserialize())
--FILE--
<?php
class S implements Serializable {
    public function serialize() { return ''; }
    public function unserialize($d) {
        try { $f = new Fiber(function () { Fiber::suspend(); }); $f->start(); echo "start ok\n"; }
        catch (FiberError $e) { echo "start: ", $e::class, ": ", $e->getMessage(), "\n"; }
    }
    public function __serialize(): array { return []; }
    public function __unserialize(array $d): void {}
}
$g = new Fiber(function () { Fiber::suspend(); });
$g->start();
class R implements Serializable {
    public function serialize() { return ''; }
    public function unserialize($d) {
        try { $GLOBALS['g']->resume(); echo "resume ok\n"; }
        catch (FiberError $e) { echo "resume: ", $e::class, ": ", $e->getMessage(), "\n"; }
    }
    public function __serialize(): array { return []; }
    public function __unserialize(array $d): void {}
}
unserialize('C:1:"S":0:{}');
unserialize('C:1:"R":0:{}');
$h = new Fiber(function () { unserialize('C:1:"T":0:{}'); });
class T implements Serializable {
    public function serialize() { return ''; }
    public function unserialize($d) {
        try { Fiber::suspend(); echo "suspend ok\n"; }
        catch (FiberError $e) { echo "suspend: ", $e::class, ": ", $e->getMessage(), "\n"; }
    }
    public function __serialize(): array { return []; }
    public function __unserialize(array $d): void {}
}
$h->start();
?>
--EXPECT--
start: FiberError: Cannot switch fibers in current execution context
resume: FiberError: Cannot switch fibers in current execution context
suspend: FiberError: Cannot switch fibers in current execution context
