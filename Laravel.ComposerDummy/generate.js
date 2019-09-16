const fs = require("fs");
const data = fs.readFileSync("../Laravel/composer.lock", { "encoding": "utf-8" });
const json = JSON.parse(data);

const set1 = JSON.parse("{" + json.packages.map(item => `"${item.name}": "${item.version}"`).join(",\r\n") + "}");

const laravelData = fs.readFileSync("../third-party/framework/composer.json");
const laravelJson = JSON.parse(laravelData);

const set2 = laravelJson.replace;

for (const key in set2) {
    if (set2[key] == "self.version") {
        set2[key] = "v6.0.3";
    }

    set1[key] = set2[key];
}

set1["laravel/framework"] = "v6.0.3";

console.log(JSON.stringify(set1, null, 4));