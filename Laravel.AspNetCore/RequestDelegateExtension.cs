using System;
using System.IO;
using System.Reflection;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Rewrite;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.FileProviders;
using Pchp.Core;
using Peachpie.AspNetCore.Web;
using Laravel.Sdk;
using Laravel.AspNetCore.Internal;

namespace Laravel.AspNetCore
{
    /// <summary>
    /// <see cref="IApplicationBuilder"/> extension for enabling Laravel.
    /// </summary>
    public static class RequestDelegateExtension
    {
        /// <summary>Redirect to `index.php` if the the file does not exist.</summary>
        static void ShortUrlRule(RewriteContext context, IFileProvider files)
        {
            var req = context.HttpContext.Request;
            var subpath = req.Path.Value;
            if (subpath != "/")
            {
                if (subpath.IndexOf("public/", StringComparison.Ordinal) != -1 ||   // it is in the wp-content -> definitely a file
                    files.GetFileInfo(subpath).Exists ||                            // the script is in the file system
                    Context.TryGetDeclaredScript(subpath.Substring(1)).IsValid ||   // the script is declared (compiled) in Context but not in the file system
                    context.StaticFileProvider.GetFileInfo(subpath).Exists ||       // the script is in the file system
                    subpath == "/favicon.ico") // 404 // even the favicon is not there, let the request proceed
                {
                    // proceed to Static Files
                    return;
                }

                if (files.GetDirectoryContents(subpath).Exists)
                {
                    var lastchar = subpath[subpath.Length - 1];
                    if (lastchar != '/' && lastchar != '\\')
                    {
                        // redirect to the directory with leading slash:
                        context.HttpContext.Response.Redirect(req.PathBase + subpath + "/" + req.QueryString, true);
                        context.Result = RuleResult.EndResponse;
                    }

                    // proceed to default document
                    return;
                }

                if (files.GetFileInfo("/public/" + subpath).Exists)
                {
                    req.Path = new PathString("/public/" + subpath);
                    context.Result = RuleResult.SkipRemainingRules;

                    return;
                }
            }

            // everything else is handled by `index.php`
            req.Path = new PathString("/public/index.php");
            context.Result = RuleResult.SkipRemainingRules;
        }

        /// <summary>
        /// Defines Laravel configuration constants and initializes runtime before proceeding to <c>index.php</c>.
        /// </summary>
        static void Apply(Context ctx, LaravelLoader loader)
        {
            // $peachpie-laravel-loader : LaravelLoader
            ctx.Globals["peachpie_laravel_loader"] = PhpValue.FromClass(loader);
        }

        /// <summary>Class <see cref="Illuminate.Foundation.Application"/> is compiled in PHP assembly <c>Laravel.dll</c>.</summary>
        static string LaravelAssemblyName => typeof(Illuminate.Foundation.Application).Assembly.FullName;

        /// <summary>
        /// Installs Laravel middleware.
        /// </summary>
        /// <param name="app">The application builder.</param>
        /// <param name="assemblies">Additional Script assemblies to load</param>
        /// <param name="path">Physical location of laravel folder. Can be absolute or relative to the current directory.</param>
        public static IApplicationBuilder UseLaravel(this IApplicationBuilder app, string[] assemblies = null, string path = null)
        {
            // laravel root path:
            if (path == null)
            {
                // bin
                path = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location) + "/";
            }

            var root = System.IO.Path.GetFullPath(path);
            var fprovider = new PhysicalFileProvider(root);

            //
            var laravelLoader = new LaravelLoader();

            // url rewriting:
            app.UseRewriter(new RewriteOptions().Add(context => ShortUrlRule(context, fprovider)));

            // log exceptions:
            // app.UseDiagnostic();

            // handling php files:
            app.UsePhp(new PhpRequestOptions()
            {
                ScriptAssembliesName = LaravelAssemblyName.ArrayConcat(assemblies),
                BeforeRequest = ctx => Apply(ctx, laravelLoader),
                RootPath = root,
            });

            // static files
            app.UseStaticFiles(new StaticFileOptions() { FileProvider = fprovider });

            return app;
        }
    }
}