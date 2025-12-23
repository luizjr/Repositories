<?php

namespace SebastianBerc\Repositories\Test;

use Illuminate\Support\Str;
use Faker\Factory as Faker;

class TestFactory
{
    protected $class;
    protected $count = 1;

    public function __construct($class = null)
    {
        $this->class = $class;
    }

    public function times($count)
    {
        $this->count = $count;
        return $this;
    }

    public function create($class = null, $attributes = [])
    {
        if ($class) {
            $this->class = $class;
        }

        $results = [];
        for ($i = 0; $i < $this->count; $i++) {
            $data = $this->getAttributes($this->class);
            $data = array_merge($data, $attributes);

            // Handle closures or factory references in data
            foreach ($data as $key => $value) {
                if (is_callable($value)) {
                    $data[$key] = $value();
                }
            }

            // echo "Creating $this->class with data: " . json_encode($data) . "\n";

            $results[] = ($this->class)::create($data);
        }

        return $this->count === 1 ? $results[0] : new \Illuminate\Database\Eloquent\Collection($results);
    }

    protected function getAttributes($class)
    {
        // echo "Getting attributes for: $class\n";
        $faker = Faker::create();

        // Check for RelatedModelStub or PasswordReset first
        if (str_ends_with($class, 'RelatedModelStub') || str_ends_with($class, 'PasswordReset')) {
            return [
                'user_id' => function () {
                    // We need to create a user. We can't easily know which class to use,
                    // but ModelStub is the standard one.
                    // We need to make sure ModelStub is available.
                    $userClass = 'SebastianBerc\Repositories\Test\ModelStub';
                    return $userClass::create($this->getAttributes($userClass))->id;
                },
                'token'   => $faker->md5
            ];
        }

        // Check for ModelStub, ModelTransformStub, or User (all use users table)
        if (str_ends_with($class, 'ModelStub') || str_ends_with($class, 'ModelTransformStub') || str_ends_with($class, 'User')) {
            return [
                'email' => $faker->unique()->companyEmail,
                'password' => 'secret',
                'remember_token' => md5(Str::random())
            ];
        }

        return [];
    }
}
