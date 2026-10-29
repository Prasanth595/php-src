--TEST--
GH-24674 (GC destructor running during unserialize() gets its own context)
--FILE--
<?php
class G {
    public $self;
    public static $hits = 0;
    public function __destruct() {
        if (self::$hits++ === 0) {
            echo "destructor: ";
            var_dump(@unserialize('R:3;'));
        }
    }
}

class U {
    public function __unserialize(array $data): void {}
}

$n = 3000;
$objs = '';
$refs = '';
for ($i = 0; $i < $n; $i++) {
    $objs .= "i:$i;O:8:\"stdClass\":0:{}";
    $refs .= "i:$i;r:" . (3 + $i) . ";";
}
$payload = 'a:2:{i:0;a:' . $n . ':{' . $objs . '}i:1;O:1:"U":' . ($n + 1) . ':{' . $refs . 'i:' . $n . ';X}}';

for ($i = 0, $fill = 10000 - gc_status()['roots'] - 50; $i < $fill; $i++) {
    $g = new G;
    $g->self = $g;
}
unset($g);

var_dump(@unserialize($payload));
?>
--EXPECT--
destructor: bool(false)
bool(false)
