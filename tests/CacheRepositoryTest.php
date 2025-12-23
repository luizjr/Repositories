<?php

namespace SebastianBerc\Repositories\Test;

use PHPUnit\Framework\Attributes\Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use SebastianBerc\Repositories\Contracts\ShouldCache;
use SebastianBerc\Repositories\Exceptions\InvalidRepositoryModel;
use SebastianBerc\Repositories\Repository;

/**
 * Class CacheRepositoryTest
 *
 * @author    Sebastian Berć <sebastian.berc@gmail.com>
 * @copyright Copyright (c) Sebastian Berć
 * @package   SebastianBerc\Repositories\Test
 */
class CacheRepositoryTest extends TestCase
{
    /**
     * @var BadCacheRepositoryStub
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache      = $this->app->make('cache.store');
        $this->repository = new CacheRepositoryStub($this->app);
    }

    #[Test]
    public function itShouldReturnRepositoryInstance()
    {
        $this->assertEquals(CacheRepositoryStub::class, get_class(CacheRepositoryStub::instance()));
    }

    #[Test]
    public function itShouldReturnAllRecordsFromCache()
    {
        $this->factory()->times(5)->create(ModelStub::class);
        $collection = $this->repository->all();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals($this->repository->all(), $collection);
    }

    #[Test]
    public function isShouldPaginateRecordsFromCache()
    {
        $this->factory()->times(50)->create(ModelStub::class);
        $paginator = $this->repository->paginate(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals($this->repository->paginate(10), $paginator);
    }

    #[Test]
    public function itShouldReturnSpecifiedRecordFromCache()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->assertInstanceOf(ModelStub::class, $model);
        $this->assertEquals($model->toArray(), $this->repository->find($model->getKey())->toArray());
    }

    #[Test]
    public function itShouldCreateNewRecordInCache()
    {
        $model = $this->repository->create([
            'email'          => $this->fake()->email,
            'password'       => 'secret',
            'remember_token' => md5(\Illuminate\Support\Str::random())
        ]);

        $this->assertEquals($model->toArray(), $this->repository->find($model->getKey())->toArray());
    }

    #[Test]
    public function itShouldUpdateSpecifiedRecordInCache()
    {
        $model   = $this->factory()->create(ModelStub::class);
        $updated = $this->repository->update($model->getKey(), ['password' => 'terces']);

        $this->assertEquals($updated->toArray(), $this->repository->find($model->getKey())->toArray());
    }

    #[Test]
    public function itShouldDeleteSpecifiedRecordFromCache()
    {
        $model = $this->factory()->create(ModelStub::class);

        $this->repository->delete($model->getKey());

        $this->assertNull($this->repository->find($model->getKey()));
    }

    #[Test]
    public function itShouldFindRecordByHisField()
    {
        $this->factory()->times(15)->create(ModelStub::class);
        $model = $this->factory()->create(ModelStub::class);

        $finded = $this->repository->findBy('email', $model->email);

        $this->assertEquals($this->repository->findBy('email', $model->email), $finded);
    }

    #[Test]
    public function itShouldFindRecordWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(15)->create(ModelStub::class);
        $model = $this->factory()->create(ModelStub::class);
        $this->repository->findWhere($wheres = ['email' => $model->email, 'password' => 'secret']);

        $finded = $this->repository->findWhere($wheres);

        $this->assertEquals($finded, $this->repository->findWhere($wheres));
    }

    #[Test]
    public function itShouldFindRecordsWhereGivenFieldsAreMatch()
    {
        $this->factory()->times(17)->create(ModelStub::class);

        $finded = $this->repository->where('password', 'secret');

        $this->assertEquals($this->repository->where('password', 'secret'), $finded);
    }

    #[Test]
    public function itShouldThrowAnExceptionWhenBadObjectIsGiven()
    {
        $this->expectException(InvalidRepositoryModel::class);

        (new BadCacheRepositoryStub($this->app))->find(1);
    }

    #[Test]
    public function itShouldThrowExceptionWhenCallingBadMethod()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->repository->veryBadMethod();
    }
}

class BadCacheRepositoryStub extends Repository implements ShouldCache
{
    public function takeModel()
    {
        return BadModelStub::class;
    }
}

class BadCacheModelStub
{
    protected $table = 'users';
}
