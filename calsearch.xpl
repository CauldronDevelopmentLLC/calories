import xmlpl.curl;
import xmlpl.stdio;
import xmlpl.process;
import xmlpl.string;
import xmlpl.stdlib;

string getQValue(string[] query, string name) {
  integer i;
  for (i = 0; i + 1 < size(query); i += 2)
    if (name == query[i]) return url_unescape(query[i + 1]);

  return "";
}

node[] main() {
  string[] query = tokenize(getenv("QUERY_STRING"), "&=");
  string q = getQValue(query, "q");

  string url = "http://www.calorieking.com/foods/search.php?keywords=" +
    url_escape(q) + "\\&showresults=true";

  string cmd = "wget -q -O- " + url + " | tidy -asxhtml -q 2>/dev/null";

  "Content-type: text/html\n\n";

  <div>
  systemXML(cmd)/html/body/div[@id == "body"]/div/
    table[@class == "foodresults"]/tr/td/table[0];
  </div>
}
