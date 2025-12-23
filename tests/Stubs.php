<?php

namespace SebastianBerc\Repositories\Test;

use Illuminate\Database\Eloquent\Model;
use SebastianBerc\Repositories\Repository;
use SebastianBerc\Repositories\Contracts\ShouldCache;

class ModelStub extends Model
{
  protected $fillable = ['email', 'password', 'remember_token'];
  protected $table = 'users';

  public function token()
  {
    return $this->hasOne(RelatedModelStub::class, 'id');
  }

  public function otherToken()
  {
    return $this->hasOne(RelatedModelStub::class, 'id');
  }
}

class User extends ModelStub
{
  public function password()
  {
    return $this->hasOne(PasswordReset::class, 'id');
  }
}

class RelatedModelStub extends Model
{
  protected $fillable = ['user_id', 'token'];
  protected $table = 'password_resets';

  public function user()
  {
    return $this->belongsTo(ModelStub::class);
  }
}

class PasswordReset extends RelatedModelStub {}

class RepositoryStub extends Repository
{
  public function takeModel()
  {
    return ModelStub::class;
  }
}

class CacheRepositoryStub extends Repository implements ShouldCache
{
  public function takeModel()
  {
    return ModelStub::class;
  }
}
