﻿using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Logging;
using System;
using System.IO;

using Laravel.AspNetCore;
using Laravel.Sdk;
using Illuminate;
using Illuminate.Contracts.Http;
using App.Http.Controllers;

namespace peachserver
{
    class Program
    {
        static void Main(string[] args)
        {
            var host = new WebHostBuilder()
                .UseKestrel()
                .UseUrls("http://*:5004/")
                .UseStartup<Startup>()
                .UseContentRoot(Directory.GetCurrentDirectory())
                .ConfigureLogging(logging =>
                {
                    logging.ClearProviders();
                    logging.AddConsole();
                })
                .Build();

            host.Run();
        }
    }

    class Startup
    {
        public void ConfigureServices(IServiceCollection services)
        {
            // Adds a default in-memory implementation of IDistributedCache.
            services.AddDistributedMemoryCache();

            services.AddSession(options =>
            {
                options.IdleTimeout = TimeSpan.FromMinutes(30);
                options.Cookie.HttpOnly = true;
            });
        }

        public void Configure(IApplicationBuilder app)
        {
            app.UseLaravel(new string[] {
                typeof(App.Providers.AppServiceProvider).Assembly.FullName
            });

            LaravelLoader.AppStarted += (laravelApp) =>
            {
                laravelApp.Context.DeclareType<TestController>();
            };

            // Type type = typeof(App.Providers.AppServiceProvider).Assembly.GetType("<Root>bootstrap.app_php");
            // var method = type.GetMethod("<Main>", BindingFlags.Public | BindingFlags.Static);
            // var app = method.Invoke(null, new object[] {
                
            // });

            // Kernel app = Helpers.app("\\Illuminate\\Contracts\\Http\\Kernel");

            // Console.WriteLine("AppStarted!");
        }
    }
}
