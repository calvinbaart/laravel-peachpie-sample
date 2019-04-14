<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentBelongsToManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts_tags', function (Blueprint $table) {
            $table->integer('post_id');
            $table->integer('tag_id');
            $table->string('flag')->default('');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    public function test_basic_create_and_retrieve()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([
            $tag->id => ['flag' => 'taylor'],
            $tag2->id => ['flag' => ''],
            $tag3->id => ['flag' => 'exclude'],
        ]);

        // Tags with flag = exclude should be excluded
        $this->assertCount(2, $post->tags);
        $this->assertInstanceOf(Collection::class, $post->tags);
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertEquals($tag2->name, $post->tags[1]->name);

        // Testing on the pivot model
        $this->assertInstanceOf(Pivot::class, $post->tags[0]->pivot);
        $this->assertEquals($post->id, $post->tags[0]->pivot->post_id);
        $this->assertEquals('post_id', $post->tags[0]->pivot->getForeignKey());
        $this->assertEquals('tag_id', $post->tags[0]->pivot->getOtherKey());
        $this->assertEquals('posts_tags', $post->tags[0]->pivot->getTable());
        $this->assertEquals(
            [
                'post_id' => '1', 'tag_id' => '1', 'flag' => 'taylor',
                'created_at' => '2017-10-10 10:10:10', 'updated_at' => '2017-10-10 10:10:10',
            ],
            $post->tags[0]->pivot->toArray()
        );
    }

    public function test_refresh_on_other_model_works()
    {
        $post = Post::create(['title' => Str::random()]);
        $tag = Tag::create(['name' => $tagName = Str::random()]);

        $post->tags()->sync([
            $tag->id,
        ]);

        $post->load('tags');

        $loadedTag = $post->tags()->first();

        $tag->update(['name' => 'newName']);

        $this->assertEquals($tagName, $loadedTag->name);

        $this->assertEquals($tagName, $post->tags[0]->name);

        $loadedTag->refresh();

        $this->assertEquals('newName', $loadedTag->name);

        $post->refresh();

        $this->assertEquals('newName', $post->tags[0]->name);
    }

    public function test_custom_pivot_class()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);

        $tag = TagWithCustomPivot::create(['name' => Str::random()]);

        $post->tagsWithCustomPivot()->attach($tag->id);

        $this->assertInstanceOf(CustomPivot::class, $post->tagsWithCustomPivot[0]->pivot);
        $this->assertEquals('1507630210', $post->tagsWithCustomPivot[0]->pivot->getAttributes()['created_at']);

        $this->assertInstanceOf(CustomPivot::class, $post->tagsWithCustomPivotClass[0]->pivot);
        $this->assertEquals('posts_tags', $post->tagsWithCustomPivotClass()->getTable());

        $this->assertEquals([
            'post_id' => '1',
            'tag_id' => '1',
        ], $post->tagsWithCustomAccessor[0]->tag->toArray());

        $pivot = $post->tagsWithCustomPivot[0]->pivot;
        $pivot->tag_id = 2;
        $pivot->save();

        $this->assertEquals(1, CustomPivot::count());
        $this->assertEquals(1, CustomPivot::first()->post_id);
        $this->assertEquals(2, CustomPivot::first()->tag_id);
    }

    public function test_attach_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);
        $tag5 = Tag::create(['name' => Str::random()]);
        $tag6 = Tag::create(['name' => Str::random()]);
        $tag7 = Tag::create(['name' => Str::random()]);
        $tag8 = Tag::create(['name' => Str::random()]);

        $post->tags()->attach($tag->id);
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertNotNull($post->tags[0]->pivot->created_at);

        $post->tags()->attach($tag2->id, ['flag' => 'taylor']);
        $post->load('tags');
        $this->assertEquals($tag2->name, $post->tags[1]->name);
        $this->assertEquals('taylor', $post->tags[1]->pivot->flag);

        $post->tags()->attach([$tag3->id, $tag4->id]);
        $post->load('tags');
        $this->assertEquals($tag3->name, $post->tags[2]->name);
        $this->assertEquals($tag4->name, $post->tags[3]->name);

        $post->tags()->attach([$tag5->id => ['flag' => 'mohamed'], $tag6->id => ['flag' => 'adam']]);
        $post->load('tags');
        $this->assertEquals($tag5->name, $post->tags[4]->name);
        $this->assertEquals('mohamed', $post->tags[4]->pivot->flag);
        $this->assertEquals($tag6->name, $post->tags[5]->name);
        $this->assertEquals('adam', $post->tags[5]->pivot->flag);

        $post->tags()->attach(new Collection([$tag7, $tag8]));
        $post->load('tags');
        $this->assertEquals($tag7->name, $post->tags[6]->name);
        $this->assertEquals($tag8->name, $post->tags[7]->name);
    }

    public function test_detach_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);
        $tag5 = Tag::create(['name' => Str::random()]);
        Tag::create(['name' => Str::random()]);
        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals(Tag::pluck('name'), $post->tags->pluck('name'));

        $post->tags()->detach($tag->id);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $post->tags()->detach([$tag2->id, $tag3->id]);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $post->tags()->detach(new Collection([$tag4, $tag5]));
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id, $tag4->id, $tag5->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $this->assertCount(2, $post->tags);
        $post->tags()->detach();
        $post->load('tags');
        $this->assertCount(0, $post->tags);
    }

    public function test_first_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->name, $post->tags()->first()->name);
    }

    public function test_firstOrFail_method()
    {
        $this->expectException(ModelNotFoundException::class);

        $post = Post::create(['title' => Str::random()]);

        $post->tags()->firstOrFail(['id' => 10]);
    }

    public function test_find_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag2->name, $post->tags()->find($tag2->id)->name);
        $this->assertCount(2, $post->tags()->findMany([$tag->id, $tag2->id]));
    }

    public function test_findOrFail_method()
    {
        $this->expectException(ModelNotFoundException::class);

        $post = Post::create(['title' => Str::random()]);

        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->findOrFail(10);
    }

    public function test_findOrNew_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->findOrNew($tag->id)->id);

        $this->assertNull($post->tags()->findOrNew('asd')->id);
        $this->assertInstanceOf(Tag::class, $post->tags()->findOrNew('asd'));
    }

    public function test_firstOrNew_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->firstOrNew(['id' => $tag->id])->id);

        $this->assertNull($post->tags()->firstOrNew(['id' => 'asd'])->id);
        $this->assertInstanceOf(Tag::class, $post->tags()->firstOrNew(['id' => 'asd']));
    }

    public function test_firstOrCreate_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->firstOrCreate(['name' => $tag->name])->id);

        $new = $post->tags()->firstOrCreate(['name' => 'wavez']);
        $this->assertEquals('wavez', $new->name);
        $this->assertNotNull($new->id);
    }

    public function test_updateOrCreate_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->updateOrCreate(['id' => $tag->id], ['name' => 'wavez']);
        $this->assertEquals('wavez', $tag->fresh()->name);

        $post->tags()->updateOrCreate(['id' => 'asd'], ['name' => 'dives']);
        $this->assertNotNull($post->tags()->whereName('dives')->first());
    }

    public function test_sync_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([$tag->id, $tag2->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $output = $post->tags()->sync([$tag->id, $tag3->id, $tag4->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag3->id, $tag4->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $this->assertEquals([
            'attached' => [$tag3->id, $tag4->id],
            'detached' => [1 => $tag2->id],
            'updated' => [],
        ], $output);

        $post->tags()->sync([]);
        $this->assertEmpty($post->load('tags')->tags);

        $post->tags()->sync([
            $tag->id => ['flag' => 'taylor'],
            $tag2->id => ['flag' => 'mohamed'],
        ]);
        $post->load('tags');
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertEquals('taylor', $post->tags[0]->pivot->flag);
        $this->assertEquals($tag2->name, $post->tags[1]->name);
        $this->assertEquals('mohamed', $post->tags[1]->pivot->flag);
    }

    public function test_syncWithoutDetaching_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([$tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->syncWithoutDetaching([$tag2->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );
    }

    public function test_toggle_method()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->toggle([$tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->toggle([$tag2->id, $tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->toggle([$tag2->id, $tag->id => ['flag' => 'taylor']]);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->tags->pluck('name')
        );
        $this->assertEquals('taylor', $post->tags[0]->pivot->flag);
    }

    public function test_touching_parent()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = TouchingTag::create(['name' => Str::random()]);

        $post->touchingTags()->attach([$tag->id]);

        $this->assertNotEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->update(['name' => $tag->name]);
        $this->assertNotEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

        $tag->update(['name' => Str::random()]);
        $this->assertEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    }

    public function test_touching_related_models_on_sync()
    {
        $tag = TouchingTag::create(['name' => Str::random()]);

        $post = Post::create(['title' => Str::random()]);

        $this->assertNotEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotEquals('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->posts()->sync([$post->id]);

        $this->assertEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertEquals('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
    }

    public function test_no_touching_happens_if_not_configured()
    {
        $tag = Tag::create(['name' => Str::random()]);

        $post = Post::create(['title' => Str::random()]);

        $this->assertNotEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotEquals('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->posts()->sync([$post->id]);

        $this->assertNotEquals('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotEquals('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
    }

    public function test_can_retrieve_related_ids()
    {
        $post = Post::create(['title' => Str::random()]);

        DB::table('tags')->insert([
            ['id' => 200, 'name' => 'excluded'],
            ['id' => 300, 'name' => Str::random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
        ]);

        $this->assertEquals([200, 400], $post->tags()->allRelatedIds()->toArray());
    }

    public function test_can_touch_related_models()
    {
        $post = Post::create(['title' => Str::random()]);

        DB::table('tags')->insert([
            ['id' => 200, 'name' => Str::random()],
            ['id' => 300, 'name' => Str::random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
        ]);

        Carbon::setTestNow('2017-10-10 10:10:10');

        $post->tags()->touch();

        foreach ($post->tags()->pluck('tags.updated_at') as $date) {
            $this->assertEquals('2017-10-10 10:10:10', $date);
        }

        $this->assertNotEquals('2017-10-10 10:10:10', Tag::find(300)->updated_at);
    }

    public function test_can_update_existing_pivot()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertEquals('exclude', $tag->pivot->flag);
        }
    }

    public function test_can_update_existing_pivot_using_arrayable_of_ids()
    {
        $tags = new Collection([
            $tag1 = Tag::create(['name' => Str::random()]),
            $tag2 = Tag::create(['name' => Str::random()]),
        ]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'empty'],
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tags, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertEquals('exclude', $tag->pivot->flag);
        }
    }

    public function test_can_update_existing_pivot_using_model()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tag, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertEquals('exclude', $tag->pivot->flag);
        }
    }

    public function test_custom_related_key()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = $post->tagsWithCustomRelatedKey()->create(['name' => Str::random()]);
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_id);

        $post->tagsWithCustomRelatedKey()->detach($tag);

        $post->tagsWithCustomRelatedKey()->attach($tag);
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_id);

        $post->tagsWithCustomRelatedKey()->detach(new Collection([$tag]));

        $post->tagsWithCustomRelatedKey()->attach(new Collection([$tag]));
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_id);

        $post->tagsWithCustomRelatedKey()->updateExistingPivot($tag, ['flag' => 'exclude']);
        $this->assertEquals('exclude', $post->tagsWithCustomRelatedKey()->first()->pivot->flag);
    }

    public function test_global_scope_columns()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $tags = $post->tagsWithGlobalScope;

        $this->assertEquals(['id' => 1], $tags[0]->getAttributes());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $touches = ['touchingTags'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag')
            ->withTimestamps()
            ->wherePivot('flag', '<>', 'exclude');
    }

    public function tagsWithExtraPivot()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag');
    }

    public function touchingTags()
    {
        return $this->belongsToMany(TouchingTag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withTimestamps();
    }

    public function tagsWithCustomPivot()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(CustomPivot::class)
            ->withTimestamps();
    }

    public function tagsWithCustomPivotClass()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, CustomPivot::class, 'post_id', 'tag_id');
    }

    public function tagsWithCustomAccessor()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(CustomPivot::class)
            ->as('tag');
    }

    public function tagsWithCustomRelatedKey()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id', 'id', 'name')
            ->withPivot('flag');
    }

    public function tagsWithGlobalScope()
    {
        return $this->belongsToMany(TagWithGlobalScope::class, 'posts_tags', 'post_id', 'tag_id');
    }
}

class Tag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class TouchingTag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $touches = ['posts'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class TagWithCustomPivot extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class CustomPivot extends Pivot
{
    protected $table = 'posts_tags';
    protected $dateFormat = 'U';
}

class TagWithGlobalScope extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->select('tags.id');
        });
    }
}
