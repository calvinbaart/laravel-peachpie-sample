using Pchp.Core;
using Illuminate;
using Illuminate.View;

namespace App.Http.Controllers
{
    [PhpType]
    public class BaseControllerNet : Controller
    {
        protected Context _ctx;

        public BaseControllerNet(Context ctx) : base(ctx)
        {
            this._ctx = ctx;
        }
    }
}