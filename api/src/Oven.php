<?php

declare(strict_types=1);

namespace Pizza;

final class ValidationError extends \RuntimeException
{
    /** @param string[] $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The pizza did not pass inspection.');
    }
}

/**
 * Turns a selection of ingredient ids into a priced, named, scored pizza.
 */
final class Oven
{
    private const VAT = 0.10;

    /**
     * @param array<string, mixed> $selection layer id => option id or list of option ids
     * @return array<string, mixed>
     */
    public function bake(array $selection, string $chef): array
    {
        $chef = trim($chef) !== '' ? mb_substr(trim($chef), 0, 40) : 'Anonymous Chef';
        $chosen = $this->validate($selection);

        $subtotal = 0.0;
        foreach ($chosen as $options) {
            foreach ($options as $option) {
                $subtotal += $option['price'];
            }
        }

        $vat = round($subtotal * self::VAT, 2);
        $flat = array_merge(...array_values($chosen));

        return [
            'chef' => $chef,
            'name' => $this->name($chosen),
            'layers' => array_map(
                static fn (array $options, string $layerId): array => [
                    'layer' => $layerId,
                    'label' => Catalog::layer($layerId)['name'],
                    'options' => array_values($options),
                ],
                array_values($chosen),
                array_keys($chosen),
            ),
            'ingredients' => array_column($flat, 'id'),
            'price' => [
                'subtotal' => round($subtotal, 2),
                'vat' => $vat,
                'total' => round($subtotal + $vat, 2),
                'currency' => 'EUR',
            ],
            'bake' => $this->bakeInstructions($chosen),
            'verdict' => $this->verdict($chosen),
        ];
    }

    /**
     * @param array<string, mixed> $selection
     * @return array<string, list<array<string, mixed>>> layer id => chosen options
     * @throws ValidationError
     */
    private function validate(array $selection): array
    {
        $index = Catalog::optionIndex();
        $errors = [];
        $chosen = [];

        foreach (Catalog::LAYERS as $layer) {
            $raw = $selection[$layer['id']] ?? [];
            $ids = array_values(array_unique(array_filter(
                array_map(
                    static fn ($v): string => is_scalar($v) ? (string) $v : '',
                    is_array($raw) ? $raw : [$raw],
                ),
                static fn (string $v): bool => $v !== '',
            )));

            $options = [];
            foreach ($ids as $id) {
                if (!isset($index[$id])) {
                    $errors[] = sprintf('"%s" is not on the menu.', $id);
                    continue;
                }
                if ($index[$id]['layer'] !== $layer['id']) {
                    $errors[] = sprintf('"%s" does not belong on the %s layer.', $id, $layer['name']);
                    continue;
                }
                $options[] = $index[$id];
            }

            if (count($options) < $layer['min']) {
                $errors[] = sprintf('Pick at least %d for %s.', $layer['min'], $layer['name']);
            }
            if (count($options) > $layer['max']) {
                $errors[] = sprintf('At most %d for %s, you picked %d.', $layer['max'], $layer['name'], count($options));
            }

            $chosen[$layer['id']] = $options;
        }

        if ($errors !== []) {
            throw new ValidationError($errors);
        }

        return $chosen;
    }

    /** @param array<string, list<array<string, mixed>>> $chosen */
    private function name(array $chosen): string
    {
        $ids = array_column(array_merge(...array_values($chosen)), 'id');
        sort($ids);

        // A few classics get their real name back.
        $classics = [
            'fior-di-latte|napoletana|san-marzano' => 'Margherita, Unadorned',
            'basil|buffalo|napoletana|san-marzano' => 'Margherita, By The Book',
            'fior-di-latte|napoletana|pepperoni|san-marzano' => 'Pepperoni, No Notes',
            'bianca|fior-di-latte|napoletana' => 'Bianca Classica',
        ];
        $key = implode('|', $ids);
        if (isset($classics[$key])) {
            return $classics[$key];
        }

        $adjectives = ['Reckless', 'Sun-Drunk', 'Midnight', 'Honest', 'Unhinged', 'Slow-Proofed', 'Coastal', 'Stubborn', 'Velvet', 'Backroom'];
        $nouns = ['Situation', 'Manifesto', 'Experiment', 'Tradition', 'Compromise', 'Masterpiece', 'Incident', 'Ritual', 'Special', 'Draft'];

        // Deterministic: the same pizza always gets the same name.
        $hash = crc32($key);

        return sprintf(
            'The %s %s',
            $adjectives[$hash % count($adjectives)],
            $nouns[intdiv($hash, count($adjectives)) % count($nouns)],
        );
    }

    /** @param array<string, list<array<string, mixed>>> $chosen */
    private function bakeInstructions(array $chosen): array
    {
        $base = $chosen['base'][0]['id'];
        $spec = match ($base) {
            'napoletana' => ['temp' => 450, 'minutes' => 1.5],
            'thin' => ['temp' => 280, 'minutes' => 8],
            'deep-dish' => ['temp' => 220, 'minutes' => 30],
            'sourdough' => ['temp' => 300, 'minutes' => 10],
            default => ['temp' => 240, 'minutes' => 14],
        };

        $lateAdds = array_values(array_filter(
            array_merge($chosen['toppings'], $chosen['finish']),
            static fn (array $o): bool => in_array($o['id'], ['basil', 'prosciutto', 'rocket', 'hot-honey', 'balsamic', 'olive-oil', 'sea-salt'], true),
        ));

        return [
            'temperatureC' => $spec['temp'],
            'minutes' => $spec['minutes'],
            'addAfterBake' => array_column($lateAdds, 'name'),
        ];
    }

    /** @param array<string, list<array<string, mixed>>> $chosen */
    private function verdict(array $chosen): array
    {
        $ids = array_column(array_merge(...array_values($chosen)), 'id');
        $toppingCount = count($chosen['toppings']);
        $notes = [];
        $score = 70;

        if ($toppingCount === 0) {
            $score += 10;
            $notes[] = 'Naked and unafraid. Purists nod.';
        } elseif ($toppingCount <= 3) {
            $score += 15;
            $notes[] = 'Balanced. Each topping gets to say something.';
        } else {
            $score -= 10;
            $notes[] = 'Five toppings is a crowd, not a conversation.';
        }

        if (in_array('pineapple', $ids, true) && in_array('anchovy', $ids, true)) {
            $score -= 15;
            $notes[] = 'Pineapple and anchovies. Bold. Possibly a cry for help.';
        }
        if (in_array('hot-honey', $ids, true) && (in_array('nduja', $ids, true) || in_array('chili', $ids, true))) {
            $score += 8;
            $notes[] = 'Sweet-heat combo: chefs kiss.';
        }
        if (in_array('deep-dish', $ids, true) && in_array('buffalo', $ids, true)) {
            $score -= 8;
            $notes[] = 'Buffalo mozzarella in a deep dish is a soup delivery mechanism.';
        }
        if (in_array('basil', $ids, true) && in_array('san-marzano', $ids, true)) {
            $score += 5;
            $notes[] = 'Tomato and basil. Some pairs never need reinventing.';
        }
        if (count($chosen['cheese']) === 2) {
            $score += 4;
            $notes[] = 'Two cheeses, one melt. Ambitious.';
        }

        $score = max(1, min(100, $score));

        return [
            'score' => $score,
            'grade' => match (true) {
                $score >= 90 => 'Nonna would be proud',
                $score >= 78 => 'Genuinely excellent',
                $score >= 65 => 'Solid pizza',
                $score >= 50 => 'It will be eaten',
                default => 'We need to talk',
            },
            'notes' => $notes,
        ];
    }
}
