<?php

namespace SebastianBerc\Repositories\Test;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Repository;

/**
 * Class GridTest
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
class GridTest extends TestCase
{
    /**
     * @var RepositoryStub
     */
    protected $repository;

    /**
     * @var OtherGridRepositoryStub
     */
    protected $otherRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository      = new GridRepositoryStub($this->app);
        $this->otherRepository = new OtherGridRepositoryStub($this->app);
    }

    #[Test]
    public function itShouldReturnRepositoryInstance()
    {
        $this->assertEquals(GridRepositoryStub::class, get_class(GridRepositoryStub::instance()));
    }

    #[Test]
    public function itShouldFetchFirstCollectionPageFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);

        $paginator = $this->repository->fetch(1, 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
    }

    #[Test]
    public function itShouldFetchFirstCollectionPageSortedDescendingByIdsFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);

        $paginator = $this->repository->fetch(1, 5, ['*'], [], ['id' => 'desc']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(20, current($paginator->items())->getKey());
        $this->assertEquals(16, last($paginator->items())->getKey());
    }

    #[Test]
    public function itShouldFetchFirstCollectionPageSortedDescendingByRelationFieldFromDatabase()
    {
        $this->factory()->times(3)->create(PasswordReset::class);
        $this->factory()->times(1)->create(PasswordReset::class, [
            'user_id' => $this->factory()->times(1)->create(User::class, ['email' => '00000@gmail.com'])->getKey()
        ]);
        $this->factory()->times(1)->create(PasswordReset::class, ['token' => '000a0a0ea0813aef2f6c6dfd3a49c546']);

        $paginator = $this->repository->fetch(1, 5, ['*'], [], ['password.token' => 'ASC']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, current($paginator->items())->getKey());

        $paginator = $this->otherRepository->fetch(1, 5, ['*'], [], ['user.email' => 'DESC']);

        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(4, last($paginator->items())->getKey());
    }

    #[Test]
    public function isShouldFetchFirstCollectionPageFilteredByFieldFromDatabase()
    {
        $this->factory()->times(20)->create(User::class);
        $this->factory()->times(5)->create(User::class, ['password' => 'notSecret']);

        $paginator = $this->repository->fetch(1, 5, ['*'], ['password' => 'not']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
    }

    #[Test]
    public function isShouldFetchFirstCollectionPageFilteredByRelationFieldFromDatabase()
    {
        $this->factory()->times(20)->create(PasswordReset::class);
        $this->factory()->times(5)->create(PasswordReset::class, ['token' => $token = md5('token')]);

        $paginator = $this->repository->fetch(1, 5, ['*'], ['password.token' => $token]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(5, sizeof($paginator->items()));
        $this->assertEquals(5, $paginator->total());
    }

    #[Test]
    public function itShouldReturnSimplePaginatedRecordsInDatabaseAsCollection()
    {
        $this->factory()->times(15)->create(User::class);

        $collection = $this->repository->simpleFetch(1, 5);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(5, $collection->count());
        $this->assertEquals(1, $collection->first()->getKey());
        $this->assertEquals(5, $collection->last()->getKey());
    }

    #[Test]
    public function itShouldThrowAnExceptionWhenBadObjectIsGiven()
    {
        $this->expectException(InvalidRepositoryModel::class);

        (new BadRepositoryStub($this->app))->find(1);
    }

    #[Test]
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->repository->veryBadMethod();
    }
}

class GridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return User::class;
    }
}

class OtherGridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return PasswordReset::class;
    }
}

class BadGridRepositoryStub extends Repository
{
    public function takeModel()
    {
        return BadModelStub::class;
    }
}

class BadGridModelStub
{
    protected $table = 'users';
}
