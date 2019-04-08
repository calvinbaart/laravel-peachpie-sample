using Pchp.Core;
using Illuminate;
using Illuminate.View;

namespace App.Http.Controllers
{
    [PhpType]
    public class TestController : Controller
    {
        private Context _ctx;

        public TestController(Context ctx) : base(ctx)
        {
            this._ctx = ctx;
        }

        public View index()
        {
            return Helpers.view(this._ctx, "welcome");
        }
    }
}