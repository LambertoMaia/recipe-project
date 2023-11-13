import time
import requests
import sqlite3
from bs4 import BeautifulSoup
from config import load_config
from tinydb import TinyDB, Query



class Scrapper:
    def __init__(self):
        self.config = load_config("config/scrapper.json")
        self.client = requests.Session()
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer': 'https://www.google.com/',
        }

        self.db = TinyDB('receitas.json')
        self.sqlite = sqlite3.connect('db.sqlite3')
        self.cursor = self.sqlite.cursor()

    def get_recipe(self, name):
        try:
            response = self.client.get(self.config.url + '/' + name, headers=self.headers)
            print(response.status_code)
            soup = BeautifulSoup(response.text, 'html.parser')
        except Exception as e:
            print(e)

        item = "body > div.container.list-category > div > div"

        # Finding all items
        items = soup.select(item)

        for i in items:
            preparation = i.select_one('div > div > div > span')
            address     = i.select_one('div > a')
            image       = i.select_one('div > a > picture > source')
            rname       = i.select_one('div:nth-child(2) > a > h3')

            if not address or not preparation or not image or not rname:
                continue

            preparation = preparation.text
            address     = address['href']
            image       = image['data-srcset']
            rname        = rname.text

            self.db.insert({
                'name': rname,
                'preparation': preparation,
                'address': address,
                'image': image,
                'category': name
            })

            print(f'Inserted: {rname} {name}')

    def load_recipe(self):
        recipes = self.db.all()
        counter = 0

        for recipe in recipes:
            try:
                print(f'Loading recipe: {recipe["name"]}')
                request = self.client.get(recipe['address'], headers=self.headers)

                soup = BeautifulSoup(request.text, 'html.parser')
            except Exception as e:
                print(e)

            portions = soup.select_one('body > div.container.single-content > div > article > div.info-recipe.mb-3 > span:nth-child(1)')
            ingredients_list = soup.select('body > div.container.single-content > div > article > div.ingredientes.mt-4.mb-4 > ul > li')
            ingredients = []

            for ing in ingredients_list:
                ingredients.append(ing.text)

            preparation_steps = soup.select('body > div.container.single-content > div > article > div.preparo.mt-4.mb-4 > ol > li')
            steps = []

            for step in preparation_steps:
                steps.append(step.text)

            # Converting the list to be inserted into the database separated by comma
            ingredients = ', '.join(ingredients)
            steps = ', '.join(steps)

            # Inserting into the sqlite database
            self.cursor.execute(
                'INSERT INTO recipes (name, category, instructions, ingredients, measures, thumb) VALUES (?, ?, ?, ?, ?, ?)', (
                    recipe['name'], recipe['category'], steps, ingredients, portions.text, recipe['image']
                )
            )

            self.sqlite.commit()

            print(f'Inserted: {recipe["name"]}')

            time.sleep(1.5)

    def fix_time(self):
        lRecipes = self.db.all()

        for recp in lRecipes:
            query = "UPDATE recipes SET preptime = ? WHERE name = ?"
            exec = self.cursor.execute(query, (recp['preparation'], recp['name']))

            self.sqlite.commit()

            print(f'Updated: {recp["name"]}')







if __name__ == '__main__':
    worker = Scrapper()
    worker.fix_time()

