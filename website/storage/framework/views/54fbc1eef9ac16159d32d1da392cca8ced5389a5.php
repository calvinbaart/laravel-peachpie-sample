<?php $__env->startSection('code', '429'); ?>
<?php $__env->startSection('title', __('Too Many Requests')); ?>

<?php $__env->startSection('image'); ?>
    <div style="background-image: url(<?php echo e(asset('/svg/403.svg')); ?>);" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('message', __('Sorry, you are making too many requests to our servers.')); ?>

<?php echo $__env->make('errors::illustrated-layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php /* /mnt/c/Development/laravel-peachpie-sample/website/resources/views/errors/429.blade.php */ ?>