<?php

// Interfaces definieren
interface ComponentInterface {
    public function getCost(): float;
    public function getLeadTime(): int;
    public function getType(): string;
}

// Basis-Implementierung
class BaseComponent implements ComponentInterface {
    protected $data;

    public function __construct(array $dbData) {
        $this->data = $dbData;
    }

    public function getCost(): float { return (float)$this->data['cost']; }
    public function getLeadTime(): int { return (int)$this->data['lead_time_ticks']; }
    public function getType(): string { return $this->data['type']; }
}

// Spezielle Klassen (für spätere Spezial-Logik)
class SolidBooster extends BaseComponent {
    // Könnte Methode explode() haben ;)
}

class LiquidEngine extends BaseComponent {
    // Könnte Methode testFire() haben
}

class ComponentFactory {
    public static function create(array $dbData): ComponentInterface {
        return match($dbData['type']) {
            'BOOSTER' => new SolidBooster($dbData),
            'ENGINE'  => new LiquidEngine($dbData),
            default   => new BaseComponent($dbData)
        };
    }
}
?>