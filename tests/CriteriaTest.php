<?php

namespace SebastianBerc\Repositories\Test;
use PHPUnit\Framework\Attributes\Test;

use Illuminate\Database\Eloquent\Builder;
use SebastianBerc\Repositories\Criteria;
use SebastianBerc\Repositories\Exceptions\InvalidCriteria;

/**
 * Class CriteriaTest
 *
 * @author  Sebastian Berć <sebastian.berc@gmail.com>
 * @package SebastianBerc\Repositories\Test
 */
class CriteriaTest extends TestCase
{
    /**
     * @var RepositoryStub
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function itShouldNarrowRepositoryResults()
    {
        $this->factory()->times(20)->create(User::class);

        $this->assertEquals(1, (new RepositoryStub($this->app))->criteria(new CriteriaStub())->all()->count());
    }

    #[Test]
    public function itShouldNarrowRepositoryResultsFromCache()
    {
        $this->factory()->times(20)->create(User::class);

        $this->assertEquals(1, (new CacheRepositoryStub($this->app))->criteria(new CriteriaStub())->all()->count());
    }

    #[Test]
    public function itShouldRemoveCriteriaFromStack()
    {
        $this->repository = new CacheRepositoryStub($this->app);

        $this->repository->criteria(new CriteriaStub());

        $this->assertTrue($this->repository->criteria()->removeCriteria(CriteriaStub::class));
        $this->assertEquals($this->repository->criteria()->getCriterias(), []);

        $this->repository->criteria(new CriteriaStub());

        $this->assertTrue($this->repository->criteria()->removeCriteria());
        $this->assertEquals($this->repository->criteria()->getCriterias(), []);
    }

    #[Test]
    public function itShouldThrowAnExceptionWhenInvalidCriteriaIsGiven()
    {
        $this->expectException(InvalidCriteria::class);

        $this->repository = new CacheRepositoryStub($this->app);

        $this->repository->criteria('InvalidCriteria');
    }
}

class CriteriaStub extends Criteria
{
    public function execute(Builder $query)
    {
        return $query->where(['id' => 1]);
    }
}
