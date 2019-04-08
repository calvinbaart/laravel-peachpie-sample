using System;
using System.Collections.Generic;
using System.Text;

namespace Laravel.Sdk
{
    /// <summary>
    /// Object used to be called from Laravel code,
    /// instantiated into a global PHP variable <c>$peachpie_laravel_loader</c>.
    /// </summary>
    public class LaravelLoader
    {
        public delegate void AppStartedDelegate(LaravelApp app);
        public static AppStartedDelegate AppStarted;

        /// <summary>
        /// Invoked by PHP plugin implementation (peachpie-api.php) to bridge into .NET.
        /// </summary>
        public virtual void InternalAppStarted(LaravelApp app)
        {
            if (LaravelLoader.AppStarted != null)
            {
                LaravelLoader.AppStarted(app);
            }
        }
    }
}