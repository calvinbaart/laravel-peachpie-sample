<?php $__env->startSection('title', __('Too Many Requests')); ?>
<?php $__env->startSection('code', '429'); ?>
<?php $__env->startSection('message', __('Too Many Requests')); ?>

<?php echo $__env->make('errors::minimal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php /* /mnt/c/Development/laravel-peachpie-sample/website/resources/views/errors/429.blade.php */ ?>