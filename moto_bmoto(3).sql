-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 19, 2025 at 02:16 PM
-- Server version: 10.11.13-MariaDB
-- PHP Version: 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `moto_bmoto`
--

-- --------------------------------------------------------

--
-- Table structure for table `accept_deliveries`
--

CREATE TABLE `accept_deliveries` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_name` varchar(191) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_type` varchar(191) DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `causer_type` varchar(191) DEFAULT NULL,
  `properties` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `price` decimal(20,2) NOT NULL,
  `addon_category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addon_categories`
--

CREATE TABLE `addon_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL,
  `addon_limit` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addon_category_item`
--

CREATE TABLE `addon_category_item` (
  `id` int(10) UNSIGNED NOT NULL,
  `addon_category_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `house` text DEFAULT NULL,
  `landmark` text DEFAULT NULL,
  `tag` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `latitude` varchar(191) DEFAULT NULL,
  `longitude` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data` longtext DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approve_payment_histories`
--

CREATE TABLE `approve_payment_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(191) NOT NULL,
  `user_id` varchar(191) NOT NULL,
  `zone_id` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cancel_reasons`
--

CREATE TABLE `cancel_reasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cancel_reason` varchar(191) NOT NULL,
  `role_id` varchar(191) NOT NULL,
  `usage` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `discount_type` varchar(191) NOT NULL,
  `discount` varchar(191) NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `max_count` int(11) NOT NULL DEFAULT 1,
  `min_subtotal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(20,2) DEFAULT NULL,
  `subtotal_message` varchar(191) DEFAULT NULL,
  `user_type` varchar(191) DEFAULT 'ALL',
  `max_count_per_user` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `show_in_restaurant` tinyint(1) NOT NULL DEFAULT 1,
  `show_in_cart` tinyint(1) NOT NULL DEFAULT 0,
  `show_in_home` tinyint(1) NOT NULL DEFAULT 0,
  `coupon_type` int(11) NOT NULL,
  `is_used_for_delivery` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'هذا الحقل يستخدم في حال إنشاء كوبون يقوم بخصم المبلغ من أجرة التوصيل',
  `delivery_discount_percentage` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupon_restaurant`
--

CREATE TABLE `coupon_restaurant` (
  `id` int(10) UNSIGNED NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_areas`
--

CREATE TABLE `delivery_areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `areas` longtext DEFAULT NULL,
  `geojson` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_area_restaurant`
--

CREATE TABLE `delivery_area_restaurant` (
  `id` int(10) UNSIGNED NOT NULL,
  `delivery_area_id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_collections`
--

CREATE TABLE `delivery_collections` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_collection_logs`
--

CREATE TABLE `delivery_collection_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `delivery_collection_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `type` varchar(191) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_guy_active_records`
--

CREATE TABLE `delivery_guy_active_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `date` date NOT NULL,
  `online_time` datetime NOT NULL,
  `offline_time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_guy_details`
--

CREATE TABLE `delivery_guy_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `age` varchar(191) DEFAULT NULL,
  `gender` varchar(191) DEFAULT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `vehicle_number` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `commission_rate` decimal(8,2) NOT NULL DEFAULT 5.00,
  `is_notifiable` tinyint(1) DEFAULT 0,
  `max_accept_delivery_limit` int(11) NOT NULL DEFAULT 100,
  `delivery_lat` varchar(191) DEFAULT NULL,
  `delivery_long` varchar(191) DEFAULT NULL,
  `heading` varchar(191) DEFAULT NULL,
  `tip_commission_rate` decimal(8,2) NOT NULL DEFAULT 100.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cash_limit` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fixed_salary_schedulable` tinyint(1) DEFAULT 0,
  `fixed_salary_schedule_data` longtext DEFAULT NULL,
  `fixed_salary` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `favoriteable_type` varchar(191) NOT NULL,
  `favoriteable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `foodomaa_news`
--

CREATE TABLE `foodomaa_news` (
  `id` int(10) UNSIGNED NOT NULL,
  `news_id` varchar(191) NOT NULL,
  `title` text NOT NULL,
  `content` longtext DEFAULT NULL,
  `image` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `item_category_id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `price` decimal(20,2) NOT NULL,
  `old_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `image` varchar(191) DEFAULT NULL,
  `is_recommended` tinyint(1) NOT NULL,
  `is_popular` tinyint(1) NOT NULL,
  `is_new` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `desc` longtext DEFAULT NULL,
  `placeholder_image` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_veg` tinyint(1) DEFAULT NULL,
  `order_column` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_categories`
--

CREATE TABLE `item_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_column` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext NOT NULL,
  `version` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_installed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `short_name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `settings_path` longtext DEFAULT NULL,
  `update_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

CREATE TABLE `orderitems` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(20,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_order_id` varchar(191) NOT NULL,
  `orderstatus_id` int(11) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `coupon_name` varchar(191) DEFAULT NULL,
  `location` mediumtext DEFAULT NULL,
  `address` varchar(191) NOT NULL,
  `tax` decimal(20,2) DEFAULT NULL,
  `restaurant_charge` decimal(20,2) DEFAULT NULL,
  `delivery_charge` decimal(20,2) DEFAULT NULL,
  `actual_delivery_charge` decimal(12,2) DEFAULT NULL,
  `total` decimal(20,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_mode` varchar(191) NOT NULL,
  `order_comment` text DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `transaction_id` text DEFAULT NULL,
  `delivery_type` int(11) NOT NULL DEFAULT 1,
  `payable` decimal(20,2) NOT NULL DEFAULT 0.00,
  `wallet_amount` decimal(8,2) DEFAULT NULL,
  `tip_amount` decimal(8,2) DEFAULT NULL,
  `tax_amount` decimal(8,2) DEFAULT NULL,
  `coupon_amount` decimal(8,2) DEFAULT NULL,
  `sub_total` decimal(8,2) DEFAULT NULL,
  `cash_change_amount` int(11) DEFAULT NULL,
  `is_scheduled` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_date` varchar(191) DEFAULT NULL,
  `schedule_slot` varchar(191) DEFAULT NULL,
  `distance` varchar(191) DEFAULT NULL,
  `delivery_pin` varchar(191) DEFAULT '123456',
  `zone_id` int(11) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `driver_order_commission_type` varchar(191) DEFAULT NULL,
  `driver_order_commission_rate` decimal(5,2) DEFAULT NULL,
  `driver_order_commission_amount` decimal(12,2) DEFAULT NULL,
  `driver_order_tip_rate` decimal(5,2) DEFAULT NULL,
  `driver_order_tip_amount` decimal(10,2) DEFAULT NULL,
  `driver_salary` decimal(10,2) DEFAULT NULL,
  `final_profit` decimal(12,2) DEFAULT NULL,
  `restaurant_net_amount` decimal(10,2) DEFAULT NULL,
  `prep_time` datetime DEFAULT NULL,
  `actual_total` decimal(8,2) NOT NULL,
  `actual_payment_mode` varchar(191) NOT NULL,
  `commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_type` varchar(191) DEFAULT NULL,
  `cancel_reason` varchar(191) DEFAULT NULL,
  `delay_before_driver_visibility` datetime DEFAULT NULL COMMENT 'يتم تخزين فيه تاريخ وتوقيت ظهور الطلب للسائقين',
  `alert_given` tinyint(1) DEFAULT NULL COMMENT 'هذا الحقل لتمييز الطلبات التي تم إرسال إشعار للسائقين بوجودها',
  `is_accepted_by_admin` tinyint(1) DEFAULT NULL,
  `is_manual_order` tinyint(1) NOT NULL DEFAULT 0,
  `is_free_delivery` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderstatuses`
--

CREATE TABLE `orderstatuses` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_item_addons`
--

CREATE TABLE `order_item_addons` (
  `id` int(10) UNSIGNED NOT NULL,
  `orderitem_id` int(11) NOT NULL,
  `addon_category_name` varchar(191) NOT NULL,
  `addon_name` varchar(191) NOT NULL,
  `addon_price` decimal(20,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `body` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateways`
--

CREATE TABLE `payment_gateways` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_restaurant`
--

CREATE TABLE `payment_gateway_restaurant` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_gateway_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `readable_name` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `popular_geo_places`
--

CREATE TABLE `popular_geo_places` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `latitude` varchar(191) NOT NULL,
  `longitude` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_sliders`
--

CREATE TABLE `promo_sliders` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `position_id` int(11) NOT NULL DEFAULT 0,
  `size` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_tokens`
--

CREATE TABLE `push_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token` longtext NOT NULL,
  `device_type` tinytext DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `order_id` char(36) NOT NULL,
  `restaurant_id` char(36) NOT NULL,
  `delivery_id` char(36) DEFAULT NULL,
  `rating_store` int(11) DEFAULT NULL,
  `rating_delivery` int(11) DEFAULT NULL,
  `review_store` text DEFAULT NULL,
  `review_delivery` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `razorpay_data`
--

CREATE TABLE `razorpay_data` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `razorpay_order_id_first` varchar(191) NOT NULL,
  `razorpay_order_id_second` varchar(191) DEFAULT NULL,
  `razorpay_payment_id` varchar(191) DEFAULT NULL,
  `razorpay_signature` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `location_id` varchar(191) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `rating` varchar(191) DEFAULT NULL,
  `delivery_time` varchar(191) DEFAULT NULL,
  `price_range` varchar(191) DEFAULT NULL,
  `is_pureveg` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `placeholder_image` varchar(191) DEFAULT NULL,
  `latitude` varchar(191) DEFAULT NULL,
  `longitude` varchar(191) DEFAULT NULL,
  `certificate` varchar(191) DEFAULT NULL,
  `restaurant_charges` decimal(20,2) DEFAULT NULL,
  `delivery_charges` decimal(20,2) DEFAULT NULL,
  `address` text NOT NULL,
  `pincode` varchar(191) DEFAULT NULL,
  `landmark` text DEFAULT NULL,
  `sku` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `commission_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `delivery_type` int(11) NOT NULL DEFAULT 1,
  `delivery_radius` decimal(8,2) NOT NULL DEFAULT 10.00,
  `delivery_charge_type` varchar(191) NOT NULL DEFAULT 'FIXED',
  `base_delivery_charge` decimal(20,2) DEFAULT NULL,
  `base_delivery_distance` int(11) DEFAULT NULL,
  `extra_delivery_charge` decimal(20,2) DEFAULT NULL,
  `extra_delivery_distance` int(11) DEFAULT NULL,
  `min_order_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `is_notifiable` tinyint(1) DEFAULT 0,
  `auto_acceptable` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_data` longtext DEFAULT NULL,
  `is_schedulable` tinyint(1) NOT NULL DEFAULT 0,
  `order_column` int(11) DEFAULT NULL,
  `custom_message` longtext DEFAULT NULL,
  `is_orderscheduling` tinyint(1) NOT NULL DEFAULT 0,
  `custom_featured_name` varchar(191) DEFAULT NULL,
  `accept_scheduled_orders` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_slot_buffer` int(11) NOT NULL DEFAULT 30,
  `zone_id` int(11) DEFAULT NULL,
  `free_delivery_subtotal` decimal(8,2) NOT NULL DEFAULT 0.00,
  `custom_message_on_list` longtext DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `item_offers` tinyint(1) NOT NULL DEFAULT 0,
  `item_offer_min_subtotal` decimal(8,2) DEFAULT NULL,
  `free_items` longtext DEFAULT NULL,
  `free_delivery_distance` decimal(5,1) NOT NULL DEFAULT 5.0,
  `free_delivery_cost` decimal(8,2) DEFAULT NULL,
  `free_delivery_comm` decimal(8,2) DEFAULT NULL,
  `show_time_on_order_accept` tinyint(1) NOT NULL COMMENT 'هذا الحقل لتفعيل أو إلغاء تفعيل ظهور حقل لتحديد الوقت الذي يجب فيه ظهور الطلبات للسائقين',
  `is_order_need_approval_by_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'هذا الحقل عند تفعيله يجب على المشرف الموافقة على الطلب قبل ظهوره للمطعم'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_categories`
--

CREATE TABLE `restaurant_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_category_restaurant`
--

CREATE TABLE `restaurant_category_restaurant` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_category_id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_category_sliders`
--

CREATE TABLE `restaurant_category_sliders` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `image` text DEFAULT NULL,
  `image_placeholder` text DEFAULT NULL,
  `categories_ids` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_column` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_earnings`
--

CREATE TABLE `restaurant_earnings` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `is_requested` tinyint(1) NOT NULL DEFAULT 0,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `restaurant_payout_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_payouts`
--

CREATE TABLE `restaurant_payouts` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `restaurant_earning_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `status` varchar(191) NOT NULL,
  `transaction_mode` varchar(191) DEFAULT NULL,
  `transaction_id` longtext DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_user`
--

CREATE TABLE `restaurant_user` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riad`
--

CREATE TABLE `riad` (
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE `slides` (
  `id` int(10) UNSIGNED NOT NULL,
  `promo_slider_id` int(11) NOT NULL,
  `unique_id` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `image_placeholder` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_column` int(11) DEFAULT NULL,
  `model` varchar(191) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `is_locationset` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` varchar(191) DEFAULT NULL,
  `longitude` varchar(191) DEFAULT NULL,
  `radius` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_gateways`
--

CREATE TABLE `sms_gateways` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gateway_name` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_otps`
--

CREATE TABLE `sms_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `otp` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_notifications`
--

CREATE TABLE `store_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` varchar(191) NOT NULL,
  `user_id` varchar(191) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `message` longtext NOT NULL,
  `image` varchar(191) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_payout_details`
--

CREATE TABLE `store_payout_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `data` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `todo_notes`
--

CREATE TABLE `todo_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `data` longtext NOT NULL,
  `priority` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `payable_type` varchar(191) NOT NULL,
  `payable_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount` bigint(20) NOT NULL,
  `confirmed` tinyint(1) NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfers`
--

CREATE TABLE `transfers` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_type` varchar(191) NOT NULL,
  `from_id` bigint(20) UNSIGNED NOT NULL,
  `to_type` varchar(191) NOT NULL,
  `to_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('exchange','transfer','paid','refund','gift') NOT NULL DEFAULT 'transfer',
  `status_last` enum('exchange','transfer','paid','refund','gift') DEFAULT NULL,
  `deposit_id` int(10) UNSIGNED NOT NULL,
  `withdraw_id` int(10) UNSIGNED NOT NULL,
  `discount` bigint(20) NOT NULL DEFAULT 0,
  `fee` bigint(20) NOT NULL DEFAULT 0,
  `uuid` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE `translations` (
  `id` int(10) UNSIGNED NOT NULL,
  `language_name` varchar(191) NOT NULL,
  `data` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userriad`
--

CREATE TABLE `userriad` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `auth_token` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_token` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_address_id` int(11) DEFAULT 0,
  `delivery_pin` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_guy_detail_id` int(11) DEFAULT NULL,
  `avatar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `tax_number` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_ip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `is_notifiable` tinyint(4) NOT NULL DEFAULT 1,
  `referral_code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `auth_token` longtext DEFAULT NULL,
  `session_token` longtext DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `default_address_id` int(11) DEFAULT 0,
  `delivery_pin` varchar(191) DEFAULT NULL,
  `delivery_guy_detail_id` int(11) DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `tax_number` varchar(191) DEFAULT NULL,
  `user_ip` text DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `is_notifiable` tinyint(4) NOT NULL DEFAULT 1,
  `referral_code` varchar(191) DEFAULT NULL,
  `referred_by` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(10) UNSIGNED NOT NULL,
  `holder_type` varchar(191) NOT NULL,
  `holder_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `balance` bigint(20) NOT NULL DEFAULT 0,
  `decimal_places` smallint(6) NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `delivery_surcharge_active` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_surcharge_type` varchar(191) NOT NULL DEFAULT 'percentage',
  `delivery_surcharge_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_surcharge_reason` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accept_deliveries`
--
ALTER TABLE `accept_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accept_deliveries_order_id_unique` (`order_id`),
  ADD KEY `idx_user_is_complete` (`user_id`,`is_complete`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_log_log_name_index` (`log_name`),
  ADD KEY `subject` (`subject_id`,`subject_type`),
  ADD KEY `causer` (`causer_id`,`causer_type`);

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `addon_categories`
--
ALTER TABLE `addon_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `addon_category_item`
--
ALTER TABLE `addon_category_item`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approve_payment_histories`
--
ALTER TABLE `approve_payment_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cancel_reasons`
--
ALTER TABLE `cancel_reasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupon_restaurant`
--
ALTER TABLE `coupon_restaurant`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_areas`
--
ALTER TABLE `delivery_areas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_area_restaurant`
--
ALTER TABLE `delivery_area_restaurant`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_collections`
--
ALTER TABLE `delivery_collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_collection_logs`
--
ALTER TABLE `delivery_collection_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_guy_active_records`
--
ALTER TABLE `delivery_guy_active_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_guy_details`
--
ALTER TABLE `delivery_guy_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`favoriteable_id`,`favoriteable_type`),
  ADD KEY `favorites_favoriteable_type_favoriteable_id_index` (`favoriteable_type`,`favoriteable_id`),
  ADD KEY `favorites_user_id_index` (`user_id`);

--
-- Indexes for table `foodomaa_news`
--
ALTER TABLE `foodomaa_news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `item_categories`
--
ALTER TABLE `item_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locations_name_unique` (`name`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unique_order_id` (`unique_order_id`),
  ADD KEY `idx_orders_id` (`id`),
  ADD KEY `idx_orders_status` (`orderstatus_id`),
  ADD KEY `idx_orders_restaurant` (`restaurant_id`);

--
-- Indexes for table `orderstatuses`
--
ALTER TABLE `orderstatuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pages_slug_unique` (`slug`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_gateway_restaurant`
--
ALTER TABLE `payment_gateway_restaurant`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `popular_geo_places`
--
ALTER TABLE `popular_geo_places`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_sliders`
--
ALTER TABLE `promo_sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `push_tokens`
--
ALTER TABLE `push_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `razorpay_data`
--
ALTER TABLE `razorpay_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `restaurants_sku_unique` (`sku`),
  ADD UNIQUE KEY `restaurants_slug_unique` (`slug`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `restaurant_categories`
--
ALTER TABLE `restaurant_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_category_restaurant`
--
ALTER TABLE `restaurant_category_restaurant`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_category_sliders`
--
ALTER TABLE `restaurant_category_sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_earnings`
--
ALTER TABLE `restaurant_earnings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_payouts`
--
ALTER TABLE `restaurant_payouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_user`
--
ALTER TABLE `restaurant_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_gateways`
--
ALTER TABLE `sms_gateways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_otps`
--
ALTER TABLE `sms_otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_notifications`
--
ALTER TABLE `store_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_payout_details`
--
ALTER TABLE `store_payout_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `todo_notes`
--
ALTER TABLE `todo_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_uuid_unique` (`uuid`),
  ADD KEY `transactions_payable_type_payable_id_index` (`payable_type`,`payable_id`),
  ADD KEY `payable_type_ind` (`payable_type`,`payable_id`,`type`),
  ADD KEY `payable_confirmed_ind` (`payable_type`,`payable_id`,`confirmed`),
  ADD KEY `payable_type_confirmed_ind` (`payable_type`,`payable_id`,`type`,`confirmed`),
  ADD KEY `transactions_type_index` (`type`),
  ADD KEY `transactions_wallet_id_foreign` (`wallet_id`);

--
-- Indexes for table `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfers_uuid_unique` (`uuid`),
  ADD KEY `transfers_from_type_from_id_index` (`from_type`,`from_id`),
  ADD KEY `transfers_to_type_to_id_index` (`to_type`,`to_id`),
  ADD KEY `transfers_deposit_id_foreign` (`deposit_id`),
  ADD KEY `transfers_withdraw_id_foreign` (`withdraw_id`);

--
-- Indexes for table `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wallets_holder_type_holder_id_slug_unique` (`holder_type`,`holder_id`,`slug`),
  ADD KEY `wallets_holder_type_holder_id_index` (`holder_type`,`holder_id`),
  ADD KEY `wallets_slug_index` (`slug`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accept_deliveries`
--
ALTER TABLE `accept_deliveries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addon_categories`
--
ALTER TABLE `addon_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addon_category_item`
--
ALTER TABLE `addon_category_item`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approve_payment_histories`
--
ALTER TABLE `approve_payment_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cancel_reasons`
--
ALTER TABLE `cancel_reasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupon_restaurant`
--
ALTER TABLE `coupon_restaurant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_areas`
--
ALTER TABLE `delivery_areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_area_restaurant`
--
ALTER TABLE `delivery_area_restaurant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_collections`
--
ALTER TABLE `delivery_collections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_collection_logs`
--
ALTER TABLE `delivery_collection_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_guy_active_records`
--
ALTER TABLE `delivery_guy_active_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_guy_details`
--
ALTER TABLE `delivery_guy_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `foodomaa_news`
--
ALTER TABLE `foodomaa_news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_categories`
--
ALTER TABLE `item_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderstatuses`
--
ALTER TABLE `orderstatuses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateway_restaurant`
--
ALTER TABLE `payment_gateway_restaurant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `popular_geo_places`
--
ALTER TABLE `popular_geo_places`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_sliders`
--
ALTER TABLE `promo_sliders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_tokens`
--
ALTER TABLE `push_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `razorpay_data`
--
ALTER TABLE `razorpay_data`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_categories`
--
ALTER TABLE `restaurant_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_category_restaurant`
--
ALTER TABLE `restaurant_category_restaurant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_category_sliders`
--
ALTER TABLE `restaurant_category_sliders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_earnings`
--
ALTER TABLE `restaurant_earnings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_payouts`
--
ALTER TABLE `restaurant_payouts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_user`
--
ALTER TABLE `restaurant_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_gateways`
--
ALTER TABLE `sms_gateways`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_otps`
--
ALTER TABLE `sms_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_notifications`
--
ALTER TABLE `store_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_payout_details`
--
ALTER TABLE `store_payout_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `todo_notes`
--
ALTER TABLE `todo_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translations`
--
ALTER TABLE `translations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
