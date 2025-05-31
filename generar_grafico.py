import sys
import matplotlib.pyplot as plt

ingresos = int(sys.argv[1])
egresos = int(sys.argv[2])

fig, ax = plt.subplots()
categorias = ['Ingresos', 'Egresos']
valores = [ingresos, egresos]
colores = ['#4CAF50', '#F44336']

ax.bar(categorias, valores, color=colores)
ax.set_title('Resumen Financiero')
ax.set_ylabel('Monto en Pesos ($)')
for i, valor in enumerate(valores):
    ax.text(i, valor + max(valores)*0.02, f'${valor:,}', ha='center')

plt.tight_layout()
plt.savefig('grafico_financiero.png')
plt.close()
