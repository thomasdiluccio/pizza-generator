<?php

declare(strict_types=1);

namespace Pizza;

/**
 * The ingredient catalog. Layers are ordered the way they go on the pizza,
 * which is also the order the builder walks the user through.
 */
final class Catalog
{
    public const LAYERS = [
        [
            'id' => 'base',
            'name' => 'Base',
            'prompt' => 'Every pizza starts somewhere.',
            'min' => 1,
            'max' => 1,
            'options' => [
                ['id' => 'napoletana', 'name' => 'Napoletana', 'emoji' => '🫓', 'price' => 6.0, 'color' => '#e8c07d', 'note' => 'Soft, blistered, 60 seconds in a very angry oven.'],
                ['id' => 'thin', 'name' => 'Thin & Crispy', 'emoji' => '🥖', 'price' => 5.5, 'color' => '#dcae6b', 'note' => 'Snaps when you fold it. Some say that is the point.'],
                ['id' => 'deep-dish', 'name' => 'Deep Dish', 'emoji' => '🥧', 'price' => 7.5, 'color' => '#d69c52', 'note' => 'Structurally a casserole. Emotionally a pizza.'],
                ['id' => 'sourdough', 'name' => 'Sourdough', 'emoji' => '🍞', 'price' => 7.0, 'color' => '#e3b878', 'note' => 'Fermented for 72 hours by someone with strong opinions.'],
                ['id' => 'cauliflower', 'name' => 'Cauliflower', 'emoji' => '🥦', 'price' => 7.0, 'color' => '#e9ddc0', 'note' => 'It is a vegetable wearing a crust costume.'],
            ],
        ],
        [
            'id' => 'sauce',
            'name' => 'Sauce',
            'prompt' => 'The layer that decides what kind of pizza this really is.',
            'min' => 1,
            'max' => 1,
            'options' => [
                ['id' => 'san-marzano', 'name' => 'San Marzano', 'emoji' => '🍅', 'price' => 1.5, 'color' => '#c2312b', 'note' => 'Tomatoes with a passport.'],
                ['id' => 'bianca', 'name' => 'Bianca (no sauce)', 'emoji' => '🤍', 'price' => 1.0, 'color' => '#f2e6d0', 'note' => 'Olive oil, garlic, confidence.'],
                ['id' => 'pesto', 'name' => 'Basil Pesto', 'emoji' => '🌿', 'price' => 2.5, 'color' => '#5b8c3a', 'note' => 'Green. Assertive. Slightly expensive.'],
                ['id' => 'nduja', 'name' => "'Nduja", 'emoji' => '🌶️', 'price' => 3.0, 'color' => '#b02a13', 'note' => 'Spreadable spicy pork. Yes, really.'],
                ['id' => 'bbq', 'name' => 'Smoky BBQ', 'emoji' => '🔥', 'price' => 2.0, 'color' => '#7a3b1d', 'note' => 'Controversial in Naples. Beloved everywhere else.'],
            ],
        ],
        [
            'id' => 'cheese',
            'name' => 'Cheese',
            'prompt' => 'Pick the melt.',
            'min' => 1,
            'max' => 2,
            'options' => [
                ['id' => 'fior-di-latte', 'name' => 'Fior di Latte', 'emoji' => '🧀', 'price' => 2.5, 'color' => '#fdf6e3', 'note' => 'The default, and the default is good.'],
                ['id' => 'buffalo', 'name' => 'Buffalo Mozzarella', 'emoji' => '🐃', 'price' => 4.0, 'color' => '#fffaf0', 'note' => 'Wetter, richer, worth the puddle.'],
                ['id' => 'gorgonzola', 'name' => 'Gorgonzola', 'emoji' => '🫕', 'price' => 3.5, 'color' => '#e8e4c9', 'note' => 'Funk level: noticeable.'],
                ['id' => 'pecorino', 'name' => 'Pecorino', 'emoji' => '🐑', 'price' => 3.0, 'color' => '#f0e2b6', 'note' => 'Salty finisher. Use with intent.'],
                ['id' => 'vegan-mozz', 'name' => 'Vegan Mozzarella', 'emoji' => '🌱', 'price' => 3.0, 'color' => '#f7f0dd', 'note' => 'Has gotten genuinely good, stop laughing.'],
            ],
        ],
        [
            'id' => 'toppings',
            'name' => 'Toppings',
            'prompt' => 'Up to five. Restraint is a flavor.',
            'min' => 0,
            'max' => 5,
            'options' => [
                ['id' => 'pepperoni', 'name' => 'Pepperoni', 'emoji' => '🍕', 'price' => 2.5, 'color' => '#a52a2a', 'note' => 'Cups up when it is happy.'],
                ['id' => 'mushroom', 'name' => 'Mushrooms', 'emoji' => '🍄', 'price' => 1.8, 'color' => '#8b6f4e', 'note' => 'Earthy, patient, never shows off.'],
                ['id' => 'basil', 'name' => 'Fresh Basil', 'emoji' => '🌿', 'price' => 1.0, 'color' => '#3f7d20', 'note' => 'Goes on late or it goes on burnt.'],
                ['id' => 'olive', 'name' => 'Black Olives', 'emoji' => '🫒', 'price' => 1.5, 'color' => '#2f2f35', 'note' => 'Divisive. Correct, but divisive.'],
                ['id' => 'prosciutto', 'name' => 'Prosciutto', 'emoji' => '🥓', 'price' => 4.0, 'color' => '#d98b8b', 'note' => 'Add after the bake or you have made bacon.'],
                ['id' => 'pineapple', 'name' => 'Pineapple', 'emoji' => '🍍', 'price' => 1.5, 'color' => '#f0c419', 'note' => 'We are not doing this argument again.'],
                ['id' => 'chili', 'name' => 'Chili Flakes', 'emoji' => '🌶️', 'price' => 0.5, 'color' => '#cc3b1f', 'note' => 'Cheap heat. Great value.'],
                ['id' => 'artichoke', 'name' => 'Artichoke', 'emoji' => '🌵', 'price' => 2.2, 'color' => '#6b7f52', 'note' => 'The quiet pick of people who know.'],
                ['id' => 'anchovy', 'name' => 'Anchovies', 'emoji' => '🐟', 'price' => 2.0, 'color' => '#7d7f87', 'note' => 'Umami grenade.'],
                ['id' => 'egg', 'name' => 'Cracked Egg', 'emoji' => '🥚', 'price' => 1.2, 'color' => '#f7d046', 'note' => 'Breakfast pizza is a legitimate lifestyle.'],
            ],
        ],
        [
            'id' => 'finish',
            'name' => 'Finish',
            'prompt' => 'The last thing that happens before the box closes.',
            'min' => 0,
            'max' => 2,
            'options' => [
                ['id' => 'olive-oil', 'name' => 'Olive Oil Drizzle', 'emoji' => '🫗', 'price' => 0.8, 'color' => '#c8b400', 'note' => 'The good bottle, obviously.'],
                ['id' => 'hot-honey', 'name' => 'Hot Honey', 'emoji' => '🍯', 'price' => 1.5, 'color' => '#e39b1c', 'note' => 'Sweet, then a slow burn.'],
                ['id' => 'balsamic', 'name' => 'Balsamic Glaze', 'emoji' => '🖤', 'price' => 1.2, 'color' => '#3a2418', 'note' => 'Draw something fancy with it.'],
                ['id' => 'rocket', 'name' => 'Rocket / Arugula', 'emoji' => '🥬', 'price' => 1.0, 'color' => '#4a7c2f', 'note' => 'Peppery green hair for your pizza.'],
                ['id' => 'sea-salt', 'name' => 'Flaky Sea Salt', 'emoji' => '🧂', 'price' => 0.4, 'color' => '#ffffff', 'note' => 'The cheapest upgrade on this menu.'],
            ],
        ],
    ];

    /** @return array<string, array<string, mixed>> option id => option (with layer id attached) */
    public static function optionIndex(): array
    {
        static $index = null;

        if ($index === null) {
            $index = [];
            foreach (self::LAYERS as $layer) {
                foreach ($layer['options'] as $option) {
                    $option['layer'] = $layer['id'];
                    $index[$option['id']] = $option;
                }
            }
        }

        return $index;
    }

    public static function layer(string $id): ?array
    {
        foreach (self::LAYERS as $layer) {
            if ($layer['id'] === $id) {
                return $layer;
            }
        }

        return null;
    }
}
