Simple StudOn Service for fau.tv
--------------------------------

Call example:

curl -H 'Accept: application/json' -H "Authorization: Bearer {token}" https://www.studon.fau.de/vp/check/{user}/{type}/{id}

{token} is the authorization token of fau.tv for StudOn
{user}  is the IDM user name whose access has to be checked
{type}  is either "course" or "clip"
{id}    is the id of the course or clip

The response is a json array like one of the following:
{
  "access": true,
  "message_en": "Access is granted by a StudOn object.",
  "message_de": "Der Zugriff wird über ein StudOn-Objekt gewährt."
}

{
  "access": false,
  "message_en": "No access is granted for the user.",
  "message_de": "Für den Benutzer wird kein Zugriff gewährt."
}

{
  "access": false,
  "message_en": "No Reference to this clip or course is found in StudOn.",
  "message_de": "Es wurde keine Referenz auf den Clip oder Kurs gefunden."
}
