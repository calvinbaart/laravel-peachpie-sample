<?php $__env->startSection('code', '401'); ?>
<?php $__env->startSection('title', __('Unauthorized')); ?>

<?php $__env->startSection('image'); ?>
    <div style="background-image: url(<?php echo e(asset('/svg/403.svg')); ?>);" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('message', __('Sorry, you are not authorized to access this page.')); ?>

<?php echo $__env->make('errors::illustrated-layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php /* /mnt/c/Development/laravel-peachpie-sample/website/resources/views/errors/401.blade.php */ ?>