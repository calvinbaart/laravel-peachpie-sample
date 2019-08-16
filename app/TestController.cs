using Pchp.Core;
using Illuminate;
using Illuminate.View;

namespace App.Http.Controllers
{
    [PhpType]
    public class TestController : BaseControllerNet
    {
        public TestController(Context ctx) : base(ctx) {}

        public View index()
        {
            return Helpers.view(this._ctx, "welcome");
        }
    }
}