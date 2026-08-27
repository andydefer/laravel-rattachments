<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Database\Factories;

use AndyDefer\LaravelRattachments\Enums\RatingLevel;
use AndyDefer\LaravelRattachments\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Rating>
 */
final class RatingFactory extends Factory
{
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rater_type' => 'App\Models\User',
            'rater_id' => 1,
            'rateable_type' => 'App\Models\Product',
            'rateable_id' => 1,
            'rating_level' => $this->faker->randomElement(RatingLevel::cases()),
            'review' => $this->faker->optional(0.7)->sentence(10),
            'metadata' => [
                'ip' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'source' => $this->faker->randomElement(['web', 'mobile', 'api']),
            ],
        ];
    }

    /**
     * Set a specific rating level.
     */
    public function level(RatingLevel $level): self
    {
        return $this->state(['rating_level' => $level]);
    }

    /**
     * Set rating to 5 stars (Excellent).
     */
    public function fiveStars(): self
    {
        return $this->state(['rating_level' => RatingLevel::FIVE]);
    }

    /**
     * Set rating to 4 stars (Good).
     */
    public function fourStars(): self
    {
        return $this->state(['rating_level' => RatingLevel::FOUR]);
    }

    /**
     * Set rating to 3 stars (Average).
     */
    public function threeStars(): self
    {
        return $this->state(['rating_level' => RatingLevel::THREE]);
    }

    /**
     * Set rating to 2 stars (Poor).
     */
    public function twoStars(): self
    {
        return $this->state(['rating_level' => RatingLevel::TWO]);
    }

    /**
     * Set rating to 1 star (Terrible).
     */
    public function oneStar(): self
    {
        return $this->state(['rating_level' => RatingLevel::ONE]);
    }

    /**
     * Add a review to the rating.
     */
    public function withReview(string $review): self
    {
        return $this->state(['review' => $review]);
    }

    /**
     * Set the rater (the entity creating the rating).
     */
    public function rater(Model $model): self
    {
        return $this->state([
            'rater_type' => $model->getMorphClass(),
            'rater_id' => $model->getKey(),
        ]);
    }

    /**
     * Set the rateable (the entity being rated).
     */
    public function rateable(Model $model): self
    {
        return $this->state([
            'rateable_type' => $model->getMorphClass(),
            'rateable_id' => $model->getKey(),
        ]);
    }

    /**
     * Set custom metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return $this->state(['metadata' => $metadata]);
    }

    /**
     * Create a rating from a specific source.
     */
    public function fromSource(string $source): self
    {
        return $this->state(function (array $attributes) use ($source) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['source'] = $source;

            return ['metadata' => $metadata];
        });
    }

    /**
     * Create a rating without metadata.
     */
    public function withoutMetadata(): self
    {
        return $this->state(['metadata' => null]);
    }

    /**
     * Create a rating without a review.
     */
    public function withoutReview(): self
    {
        return $this->state(['review' => null]);
    }

    /**
     * Create a rating with a long review.
     */
    public function withLongReview(): self
    {
        return $this->state([
            'review' => $this->faker->paragraph(5),
        ]);
    }

    /**
     * Create a rating with a short review.
     */
    public function withShortReview(): self
    {
        return $this->state([
            'review' => $this->faker->sentence(3),
        ]);
    }

    /**
     * Create a rating with random metadata.
     */
    public function withRandomMetadata(): self
    {
        return $this->state(function () {
            return [
                'metadata' => [
                    'ip' => $this->faker->ipv4(),
                    'user_agent' => $this->faker->userAgent(),
                    'source' => $this->faker->randomElement(['web', 'mobile', 'api']),
                    'session_id' => $this->faker->uuid(),
                    'timestamp' => $this->faker->dateTime()->format('Y-m-d H:i:s'),
                ],
            ];
        });
    }

    /**
     * Configure the factory for a specific rating level with label.
     */
    public function withLabel(string $label): self
    {
        $level = match (strtolower($label)) {
            'excellent', 'five', '5' => RatingLevel::FIVE,
            'good', 'four', '4' => RatingLevel::FOUR,
            'average', 'three', '3' => RatingLevel::THREE,
            'poor', 'two', '2' => RatingLevel::TWO,
            'terrible', 'one', '1' => RatingLevel::ONE,
            default => RatingLevel::THREE,
        };

        return $this->state(['rating_level' => $level]);
    }

    /**
     * Create a rating with all fields explicitly set.
     */
    public function complete(
        Model $rater,
        Model $rateable,
        RatingLevel $level,
        string $review,
        array $metadata = []
    ): self {
        return $this->state([
            'rater_type' => $rater->getMorphClass(),
            'rater_id' => $rater->getKey(),
            'rateable_type' => $rateable->getMorphClass(),
            'rateable_id' => $rateable->getKey(),
            'rating_level' => $level,
            'review' => $review,
            'metadata' => $metadata,
        ]);
    }
}
