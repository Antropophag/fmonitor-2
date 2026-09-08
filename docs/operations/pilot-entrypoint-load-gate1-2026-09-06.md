# Local preview entrypoint loading — Gate1

Reviewer `/root/preview_load_review`, новый gpt-5.6-sol low/fork none; root author.
v0.1 SHA374e14df9928b504bbbbdbad409baa2c98921b33c769c7229b5a4032ab9293ef
на e2d886c получил CHANGES_REQUESTED: conflict с PILOT-HTTP-AUTH-001 section11.3.

v0.2 **APPROVED** на `0b08ec7cfa952c9aa2de629bf9d4877401bf5fdf`, spec SHA
d40d1c675ee88c272a040e8ebed89768b60a7a18d0e50aad14809cb97d7937d3.
Narrow supersession касается require_once двух canonical __DIR__ dependencies;
никаких mutable paths/class_exists/autoloader substitution. Shadow classes
по-прежнему fail closed.4positive canonical/repeated и2negative shadow child
cases имеют exact observable outputs. Reviewer не редактировал source/tests.
