using System;
using Pchp.Core;

namespace Laravel.Sdk
{
    [PhpType]
    public abstract class LaravelApp
    {
        /// <summary>
        /// Minimal constructor that initializes runtime context.
        /// The .ctor is called implicitly by derived PHP class.
        /// </summary>
        protected LaravelApp(Context ctx)
        {
            _ctx = ctx;
        }

        /// <summary>
        /// Runtime context of the application.
        /// </summary>
        public Context Context => _ctx;

        /// <summary>
        /// Runtime context of the application.
        /// Special signature recognized by the compiler.
        /// </summary>
        protected readonly Context _ctx;

        public abstract string GetVersion();

        public abstract void InternalRegisterController(PhpValue controller);

        public abstract void InternalSetUserModel(PhpValue model);
    }
}