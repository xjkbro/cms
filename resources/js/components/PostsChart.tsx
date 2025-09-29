import React from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface PostsChartProps {
  data: Array<{
    date: string;
    count: number;
    formatted_date: string;
  }>;
  currentTimeframe: string;
  onTimeframeChange: (timeframe: string) => void;
}

export function PostsChart({ data, currentTimeframe, onTimeframeChange }: PostsChartProps) {
  const getChartTitle = () => {
    switch (currentTimeframe) {
      case 'year':
        return 'Posts Created - Past Year';
      case 'month':
        return 'Posts Created - Past Month';
      default:
        return 'Posts Created - All Time';
    }
  };

  return (
    <Card className="col-span-full">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>{getChartTitle()}</CardTitle>
          <div className="flex gap-2">
            <Button
              variant={currentTimeframe === 'all' ? 'default' : 'outline'}
              size="sm"
              onClick={() => onTimeframeChange('all')}
            >
              All Time
            </Button>
            <Button
              variant={currentTimeframe === 'year' ? 'default' : 'outline'}
              size="sm"
              onClick={() => onTimeframeChange('year')}
            >
              Past Year
            </Button>
            <Button
              variant={currentTimeframe === 'month' ? 'default' : 'outline'}
              size="sm"
              onClick={() => onTimeframeChange('month')}
            >
              Past Month
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="h-[400px]">
          {data.length > 0 ? (
            <ResponsiveContainer width="100%" height="100%">
              <LineChart
                data={data}
                margin={{
                  top: 5,
                  right: 30,
                  left: 20,
                  bottom: 5,
                }}
              >
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis 
                  dataKey="formatted_date" 
                  className="text-xs fill-muted-foreground"
                  tick={{ fontSize: 12 }}
                />
                <YAxis 
                  className="text-xs fill-muted-foreground"
                  tick={{ fontSize: 12 }}
                />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: 'var(--radius)',
                  }}
                  labelStyle={{ color: 'hsl(var(--card-foreground))' }}
                />
                <Line
                  type="monotone"
                  dataKey="count"
                  stroke="hsl(var(--primary))"
                  strokeWidth={2}
                  dot={{ fill: 'hsl(var(--primary))', strokeWidth: 2, r: 4 }}
                  activeDot={{ r: 6, stroke: 'hsl(var(--primary))', strokeWidth: 2 }}
                />
              </LineChart>
            </ResponsiveContainer>
          ) : (
            <div className="flex items-center justify-center h-full">
              <div className="text-center">
                <p className="text-muted-foreground">No posts data available</p>
                <p className="text-sm text-muted-foreground mt-1">
                  Create some posts to see your activity over time
                </p>
              </div>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
