<?php

namespace App;

use Event;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;
use ChristianKuri\LaravelFavorite\Traits\Favoriteable;

class Restaurant extends Model implements Sortable
{
    use SortableTrait, Favoriteable;

    /**
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];
protected $fillable = [
        'name',
        'description',
        'image',
        'delivery_time',
        'price_range',
        'is_pureveg',
        'certificate',
        'address',
        'pincode',
        'landmark',
        'latitude',
        'longitude',
        'restaurant_charges',
        'min_order_price',
        'delivery_type',
        'is_schedulable', 
        'accept_scheduled_orders',
        'schedule_slot_buffer',
        'store_payment_gateways',
        'commission_rate',
        'slug',
        'is_active',
        'is_accepted',
        'is_featured',
        'is_notifiable',
        'auto_acceptable',
        'schedule_data',
        'custom_message',
        'custom_message_on_list',
        'free_delivery_subtotal',
        'delivery_charge_type',
        'delivery_charges',
        'base_delivery_charge',
        'base_delivery_distance',
        'extra_delivery_charge',
        'extra_delivery_distance',
        'free_delivery_distance',
        'free_delivery_cost',
        'free_delivery_comm',
        'show_time_on_order_accept',
        'is_order_need_approval_by_admin',
    ];
    /**
     * @var array
     */
    protected $casts = [
        'is_active' => 'integer',
        'is_accepted' => 'integer',
        'is_featured' => 'integer',
        'delivery_type' => 'integer',
        'delivery_radius' => 'integer',
        'base_delivery_distance' => 'integer',
        'extra_delivery_distance' => 'integer',
        'distance' => 'float',
        'is_operational' => 'boolean',
        'is_favorited' => 'boolean',
        'is_orderscheduling' => 'boolean',
        'is_scheduled' => 'integer',
        'accept_scheduled_orders' => 'integer',
        'schedule_slot_buffer' => 'integer',
        'free_delivery_subtotal' => 'float',
        'is_pureveg' => 'integer',
        'is_schedulable' => 'integer',
    ];

    /**
     * @var array
     */
    protected $hidden = array('created_at', 'updated_at');

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new \App\Scopes\ZoneScope);

        static::created(function ($restaurant) {
            Event::dispatch('store.created', $restaurant);
        });

        static::updated(function ($restaurant) {
            Event::dispatch('store.updated', $restaurant);
        });

        static::deleted(function ($restaurant) {
            Event::dispatch('store.deleted', $restaurant);
        });
    }

    /**
     * @return mixed
     */
    public function items()
    {
        return $this->hasMany('App\Item');
    }

    /**
     * @return mixed
     */
    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    /**
     * @return mixed
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return mixed
     */
    public function restaurant_categories()
    {
        return $this->belongsToMany('App\RestaurantCategory', 'restaurant_category_restaurant');
    }

    /**
     * @return mixed
     */
    public function toggleActive()
    {
        $this->is_active = !$this->is_active;
        return $this;
    }

    /**
     * @return mixed
     */
    public function toggleAcceptance()
    {
        $this->is_accepted = !$this->is_accepted;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        $this->where('is_active', 1);
        return $this;
    }
    /**
     * @return mixed
     */
    public function isNotActive()
    {
        $this->where('is_active', 0);
        return $this;
    }

    /**
     * @return mixed
     */
    public function delivery_areas()
    {
        if (class_exists("\Modules\DeliveryAreaPro\Entities\DeliveryArea")) {
            return $this->belongsToMany(\Modules\DeliveryAreaPro\Entities\DeliveryArea::class);
        }
        return $this->belongsToMany(User::class);
    }

    /**
     * @return mixed
     */
    public function payment_gateways()
    {
        return $this->belongsToMany(\App\PaymentGateway::class);
    }

    /**
     * @return mixed
     */
    public function payment_gateways_active()
    {
        return $this->belongsToMany(\App\PaymentGateway::class)->where('payment_gateways.is_active', '1');
    }

    /**
     * @return mixed
     */
    public function store_payout_details()
    {
        return $this->hadMany('App\StorePayoutDetail');
    }

    /**
     * @return mixed
     */
    public function ratings()
    {
        return $this->hasMany('App\Rating');
    }

    /**
     * @return mixed
     */
    public function avgRating()
    {
        // return number_format((float) $this->ratings->avg('rating_store'), 1, '.', '');

        $avg = $this->ratings->avg('rating_store');
        return $avg;
    }

    public function zone()
    {
        return $this->belongsTo('App\Zone');
    }

    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->columns, (array) $value));
    }

    public function restaurant_earnings()
    {
        return $this->hasMany('App\RestaurantEarning');
    }

    public function restaurant_payouts()
    {
        return $this->hasMany('App\RestaurantPayout');
    }

    public function coupons()
    {
        return $this->belongsToMany('App\Coupon');
    }
}
